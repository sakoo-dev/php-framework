<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ExceptionInterface;
use Mcp\Exception\InvalidArgumentException;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Mcp\Schema\Result\CallToolResult;
use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Finder\FileFinder;
use Sakoo\Framework\Core\Finder\Makefile;
use Sakoo\Framework\Core\Kernel\Kernel;
use System\Path\Path;

/**
 * Declares all MCP capabilities (Tools, Resources, Prompts) for the Sakoo PHP Framework.
 *
 * Every tool method returns a {@see CallToolResult} from the official php-mcp/schema
 * package, providing first-class MCP protocol compatibility. The SDK serialises
 * CallToolResult directly into the JSON-RPC response — no wrapping needed.
 *
 * Response conventions:
 *   - {@see CallToolResult::success()} for successful operations.
 *   - {@see CallToolResult::error()} for tool-level failures (LLM can self-correct).
 *   - {@see TextContent} carries the human/LLM-readable text payload.
 *   - `structuredContent` carries machine-readable JSON for complex data.
 *   - `_meta` carries optional metadata (counts, truncation flags).
 *
 * Architecture:
 *   - Tools    -> LLM invokes. Return CallToolResult.
 *   - Resources -> User attaches. Return raw data.
 *   - Prompts  -> User runs as template. Return PromptMessage[].
 *
 * @see McpPathGuard           Path-traversal protection.
 * @see Shell                  Project-scoped shell executor.
 * @see ExceptionInterface     Base marker for all MCP SDK exceptions.
 */
class McpElements
{
	//	#[McpTool('rhel_integration', 'Returns Interactive Shell on the Project')]
	//	#[McpTool('route_inspection', 'Scans the project for route definition files and returns their contents.')]
	//	#[McpTool('database_connections', 'Lists all configured database connections read from environment variables (DB_*).')]
	//	#[McpTool('database_query', 'Executes a read-only SQL query against the default PDO connection and returns results.')]
	//	#[McpTool('database_schema', 'Returns the schema (tables and columns) of the configured database.')]

	/** Maximum characters of tool output to include in check_code structured results. */
	private const CHECK_OUTPUT_CAP = 10000;

	private Shell $shell;
	private McpTokenObserver $observer;

	public function __construct()
	{
		$this->shell = resolve(Shell::class);
		$this->observer = resolve(McpTokenObserver::class);
	}

	/**
	 * Reads file content with optional line-range and character-limit controls.
	 *
	 * @param string $path     relative or absolute path (scoped to project root)
	 * @param int    $from     start line number, 1-based (default: 1)
	 * @param int    $to       end line number, inclusive; 0 means EOF (default: 0)
	 * @param int    $maxChars maximum characters to return (default: 50000)
	 *
	 * @throws InvalidArgumentException when the path escapes the project scope
	 */
	#[McpTool('read_file', 'Reads file content. Use `from`/`to` for line ranges, `maxChars` to cap size. Scoped to project.')]
	public function readFileTool(string $path, int $from = 1, int $to = 0, int $maxChars = 50000): CallToolResult
	{
		$path = McpPathGuard::guard($path);
		$input = compact('path', 'from', 'to', 'maxChars');

		Assert::lazy()
			->file($path, "Not a file: {$path}")
			->notDir($path, "Is a directory: {$path}")
			->validate();

		$chunk = File::open(Disk::Local, $path)->readChunk($from, $to, $maxChars);

		if (!$chunk) {
			return CallToolResult::error([new TextContent("Cannot read: {$path}")]);
		}

		$result = CallToolResult::success(
			[new TextContent($chunk['content'])],
			['path' => $path, 'from' => $chunk['from'], 'to' => $chunk['to'], 'totalLines' => $chunk['totalLines'], 'truncated' => $chunk['truncated']],
		);

		$this->observer->log('read_file', $input, $chunk['content']);

		return $result;
	}

	/**
	 * Writes content to a file inside the project.
	 *
	 * @param string $path    relative or absolute path (scoped to project root)
	 * @param string $content file content to write
	 *
	 * @throws InvalidArgumentException when the path escapes the project scope
	 */
	#[McpTool('write_file', 'Writes content to a file. Scoped to project.')]
	public function writeFileTool(string $path, string $content): CallToolResult
	{
		$path = McpPathGuard::guard($path);
		$stored = File::open(Disk::Local, $path)->write($content);

		if (!$stored) {
			return CallToolResult::error([new TextContent("Write failed: {$path}")]);
		}

		$result = new CallToolResult(
			[new TextContent("ok:{$path}")],
			structuredContent: ['path' => $path],
		);

		$this->observer->log('write_file', compact('path'), "ok:{$path}");

		return $result;
	}

	/**
	 * Removes (deletes) a file from the project.
	 *
	 * @param string $path relative or absolute path (scoped to project root)
	 *
	 * @throws InvalidArgumentException when the path escapes the project scope
	 */
	#[McpTool('remove_file', 'Deletes a file from the project. Scoped to project. Refuses directories.')]
	public function removeFileTool(string $path): CallToolResult
	{
		$path = McpPathGuard::guard($path);

		if (!is_file($path)) {
			return CallToolResult::error([new TextContent("Not found: {$path}")]);
		}

		if (is_dir($path)) {
			return CallToolResult::error([new TextContent("Is a directory: {$path}")]);
		}

		$removed = File::open(Disk::Local, $path)->remove();

		if (!$removed) {
			return CallToolResult::error([new TextContent("Remove failed: {$path}")]);
		}

		$this->observer->log('remove_file', compact('path'), "ok:{$path}");

		return new CallToolResult(
			[new TextContent("ok:{$path}")],
			structuredContent: ['path' => $path],
		);
	}

	/**
	 * Reads multiple files in a single tool call.
	 *
	 * @param string[] $paths    array of file paths (scoped to project)
	 * @param int      $maxChars maximum characters per file (default: 30000)
	 *
	 * @throws InvalidArgumentException when any path escapes the project scope
	 */
	#[McpTool('read_files', 'Reads multiple files. Returns {path: content} map. Use maxChars to limit per-file output.')]
	public function readFilesTool(array $paths, int $maxChars = 30000): CallToolResult
	{
		$paths = McpPathGuard::guardMany($paths);
		$files = [];
		$errors = [];

		foreach ($paths as $path) {
			if (!is_file($path) || !is_readable($path)) {
				$errors[$path] = 'not_readable';

				continue;
			}

			$content = File::open(Disk::Local, $path)->readChunkText(maxChars: $maxChars);

			if (false === $content) {
				$errors[$path] = 'read_failed';

				continue;
			}

			$files[$path] = $content;
		}

		$contents = array_map(
			fn (string $p, string $c): TextContent => new TextContent("[{$p}]\n{$c}"),
			array_keys($files),
			array_values($files),
		);

		$result = new CallToolResult(
			$contents,
			structuredContent: ['files' => $files, 'errors' => $errors],
			meta: ['requested' => count($paths), 'read' => count($files)],
		);

		$this->observer->log('read_files', ['paths' => $paths, 'maxChars' => $maxChars], $files);

		return $result;
	}

	/**
	 * Lists files in a directory with optional glob pattern and result limit.
	 *
	 * @param string $path    directory path (scoped to project root)
	 * @param string $pattern glob pattern filter (e.g. '*.php'); empty = all files
	 * @param int    $limit   maximum number of files returned (default: 500)
	 *
	 * @throws InvalidArgumentException when the path escapes the project scope
	 */
	#[McpTool('dir_files', 'Lists files in a directory. Use pattern/limit to control scope. Scoped to project.')]
	public function getDirFileListTool(string $path, string $pattern = '', int $limit = 500): CallToolResult
	{
		$path = McpPathGuard::guard($path);

		$finder = (new FileFinder($path))
			->ignoreDotFiles()
			->ignoreVCS()
			->limit($limit);

		if ('' !== $pattern) {
			$finder->pattern($pattern);
		}

		$files = $finder->find();

		$result = new CallToolResult(
			[new TextContent($files)],
			structuredContent: ['files' => $files],
			meta: ['total' => count($files), 'truncated' => $finder->wasTruncated()],
		);

		$this->observer->log('dir_files', compact('path', 'pattern', 'limit'), $files);

		return $result;
	}

	/**
	 * Returns a compact project tree for orientation (app/ + core/ + system/).
	 */
	#[McpTool('project_structure', 'Compact app/ + core/ + system/ file trees (no vendor).')]
	public function projectStructureTool(): CallToolResult
	{
		$appFiles = (new FileFinder(Path::getAppDir() ?: __DIR__))
			->ignoreDotFiles()
			->ignoreVCS()
			->ignoreVCSIgnored()
			->find();

		$systemFiles = (new FileFinder(Path::getSystemDir() ?: __DIR__))
			->ignoreDotFiles()
			->ignoreVCS()
			->ignoreVCSIgnored()
			->find();

		$coreFiles = (new FileFinder(Path::getCoreDir() ?: __DIR__))
			->ignoreDotFiles()
			->ignoreVCS()
			->ignoreVCSIgnored()
			->find();

		$structured = ['app' => $appFiles, 'core' => $coreFiles, 'system' => $systemFiles];

		$result = new CallToolResult(
			[new TextContent($structured)],
			structuredContent: $structured,
		);

		$this->observer->log('project_structure', [], $structured);

		return $result;
	}

	/**
	 * Returns the latest HTTP VarDump entries from the browser log.
	 *
	 * @param int $limit maximum entries to return (default: 50)
	 */
	#[McpTool('browser_logs', 'Returns latest HTTP VarDump entries. Use limit to control size.')]
	public function browserLogsTool(int $limit = 50): CallToolResult
	{
		$logFile = Path::getStorageDir() . '/browser/http.log';
		$file = File::open(Disk::Local, $logFile);

		if (!$file->exists()) {
			return CallToolResult::success([new TextContent('No browser log found.')], ['note' => 'no_log']);
		}

		/** @var string[] $entries */
		$entries = (array) $file->readTail($limit);

		$result = new CallToolResult(
			[new TextContent($entries)],
			structuredContent: ['entries' => $entries],
		);

		$this->observer->log('browser_logs', ['limit' => $limit], $entries);

		return $result;
	}

	/**
	 * Reads framework log entries from a daily log file.
	 *
	 * @param string $date  date in Y/m/d format; empty = today
	 * @param int    $limit maximum entries to return (default: 100)
	 */
	#[McpTool('read_log_entries', 'Reads framework log entries. Filter by date (Y/m/d) and limit.')]
	public function readLogEntriesTool(string $date = '', int $limit = 100): CallToolResult
	{
		if ('' === $date) {
			$date = date('Y/m/d');
		}

		$logPath = Path::getLogsDir() . '/' . $date . '.log';
		$file = File::open(Disk::Local, $logPath);

		if (!$file->exists()) {
			return CallToolResult::success(
				[new TextContent("No log file for {$date}")],
				['date' => $date, 'n' => 0, 'note' => 'no_log'],
			);
		}

		/** @var string[] $entries */
		$entries = (array) $file->readTail($limit);

		$result = new CallToolResult(
			[new TextContent($entries)],
			structuredContent: ['entries' => $entries],
			meta: ['date' => $date, 'n' => count($entries)],
		);

		$this->observer->log('read_log_entries', compact('date', 'limit'), $entries);

		return $result;
	}

	/**
	 * Returns the last PHP error recorded via {@see error_get_last()}.
	 */
	#[McpTool('last_error', 'Returns the last PHP error via error_get_last().')]
	public function lastErrorTool(): CallToolResult
	{
		$error = error_get_last();

		if (null === $error) {
			return CallToolResult::success([new TextContent('No PHP error recorded.')]);
		}

		$data = [
			'type' => $error['type'],
			'msg' => $error['message'],
			'file' => $error['file'],
			'line' => $error['line'],
		];

		return new CallToolResult(
			[new TextContent("[{$error['type']}] {$error['message']} in {$error['file']}:{$error['line']}")],
			structuredContent: $data,
		);
	}

	/**
	 * Searches the generated wiki documentation for a keyword.
	 *
	 * @param string $keyword search keyword (case-insensitive)
	 * @param int    $limit   maximum matching lines (default: 30)
	 */
	#[McpTool('search_docs', 'Searches wiki documentation for a keyword. Returns matching lines.')]
	public function searchDocsTool(string $keyword, int $limit = 30): CallToolResult
	{
		$wikiFile = Path::getRootDir() . '/.github/wiki/Home.md';
		$file = File::open(Disk::Local, $wikiFile);

		if (!$file->exists()) {
			return CallToolResult::success(
				[new TextContent('Wiki not generated. Run: php assist doc:gen')],
				['kw' => $keyword, 'n' => 0],
			);
		}

		/** @var string[] $lines */
		$lines = (array) $file->readLines();
		$matches = [];

		foreach ($lines as $lineNo => $line) {
			if (false !== stripos($line, $keyword)) {
				$matches[] = ['ln' => $lineNo + 1, 'text' => trim($line)];
			}

			if (count($matches) >= $limit) {
				break;
			}
		}

		$result = new CallToolResult(
			[new TextContent($matches)],
			structuredContent: ['matches' => $matches],
			meta: ['kw' => $keyword, 'n' => count($matches)],
		);

		$this->observer->log('search_docs', compact('keyword', 'limit'), $matches);

		return $result;
	}

	/**
	 * Resolves an absolute URL for a given path using APP_URL from the environment.
	 *
	 * @param string $path relative URL path (default: '/')
	 */
	#[McpTool('get_absolute_url', 'Resolves absolute URL for a path using APP_URL.')]
	public function getAbsoluteUrlTool(string $path = '/'): CallToolResult
	{
		/** @var string $appUrl */
		$appUrl = Env::get('APP_URL', 'http://localhost');
		$url = rtrim($appUrl, '/') . '/' . ltrim($path, '/');

		return CallToolResult::success([new TextContent($url)]);
	}

	/**
	 * Returns recent git commits as compact {hash, msg} objects.
	 *
	 * @param int    $limit maximum commits to return (default: 20, hard max: 200)
	 * @param string $path  optional file path filter (scoped to project)
	 */
	#[McpTool('git_log', 'Recent git commits as {hash, msg}. Use limit and path to narrow.')]
	public function gitLogTool(int $limit = 20, string $path = ''): CallToolResult
	{
		if ('' !== $path) {
			McpPathGuard::guard($path);
		}

		$parsed = $this->shell->gitLogParsed($limit, $path);

		$result = new CallToolResult(
			[new TextContent($parsed['commits'])],
			structuredContent: ['commits' => $parsed['commits']],
			meta: ['n' => count($parsed['commits'])],
		);

		$this->observer->log('git_log', compact('limit', 'path'), $parsed['commits']);

		return $result;
	}

	/**
	 * Returns git diff output with truncation control.
	 *
	 * @param string $ref      git ref to diff against (empty = unstaged)
	 * @param string $path     optional file path filter (scoped to project)
	 * @param int    $maxLines maximum lines to return (default: 200, hard max: 1000)
	 */
	#[McpTool('git_diff', 'Git diff output. Defaults to unstaged. Use ref, path, maxLines to control.')]
	public function gitDiffTool(string $ref = '', string $path = '', int $maxLines = 200): CallToolResult
	{
		if ('' !== $path) {
			McpPathGuard::guard($path);
		}

		$parsed = $this->shell->gitDiffParsed($ref, $path, $maxLines);

		$result = CallToolResult::success(
			[new TextContent($parsed['diff'])],
			['lines' => $parsed['total'], 'truncated' => $parsed['truncated']],
		);

		$this->observer->log('git_diff', compact('ref', 'path', 'maxLines'), $parsed['diff']);

		return $result;
	}

	/**
	 * Shows uncommitted changes (staged, unstaged, untracked).
	 *
	 * @param string $path optional file path filter (scoped to project)
	 */
	#[McpTool('git_status', 'Shows uncommitted changes (staged, unstaged, untracked). Use before committing.')]
	public function gitStatusTool(string $path = ''): CallToolResult
	{
		if ('' !== $path) {
			McpPathGuard::guard($path);
		}

		$parsed = $this->shell->gitStatusParsed($path);

		$result = new CallToolResult(
			[new TextContent($parsed['summary'])],
			structuredContent: ['clean' => $parsed['clean'], 'files' => $parsed['files']],
			meta: ['summary' => $parsed['summary']],
		);

		$this->observer->log('git_status', ['path' => $path], $parsed['files']);

		return $result;
	}

	/**
	 * Runs PHPUnit tests with optional filter.
	 *
	 * @param string $filter optional PHPUnit filter expression
	 */
	#[McpTool('test_run', 'Runs PHPUnit tests. Optional filter. Returns pass/fail.')]
	public function testRunTool(string $filter = ''): CallToolResult
	{
		$parsed = $this->shell->phpunitParsed($filter);

		$result = new CallToolResult(
			[new TextContent($parsed['output'])],
			isError: !$parsed['ok'],
			structuredContent: ['ok' => $parsed['ok'], 'summary' => $parsed['summary']],
		);

		$this->observer->log('test_run', ['filter' => $filter], $parsed['output']);

		return $result;
	}

	/**
	 * Runs PHPUnit with code coverage and returns per-file statistics and uncovered lines.
	 *
	 * Requires Xdebug or PCOV to be enabled. Files are sorted by line coverage
	 * percentage ascending so the worst-covered files appear first.
	 *
	 * @param string $filter  optional PHPUnit filter expression
	 * @param int    $maxPct  only include files strictly below this line-coverage % (0–100); 100 = include all
	 */
	#[McpTool('test_coverage', 'Runs PHPUnit with code coverage. Returns line/method/class stats and uncovered lines per file, sorted worst first.')]
	public function testCoverageTool(string $filter = '', int $maxPct = 100): CallToolResult
	{
		$parsed = $this->shell->phpunitCoverageParsed($filter);

		$stats = $parsed['stats'];
		$files = $parsed['files'];

		if ($maxPct < 100) {
			$files = array_values(array_filter($files, static fn (array $f): bool => $f['lines_pct'] < $maxPct));
		}

		$summaryText = [] !== $stats
			? sprintf(
				'Coverage — Lines: %.2f%% (%d/%d) | Methods: %.2f%% (%d/%d) | Classes: %.2f%% (%d/%d)',
				$stats['lines_pct'],
				$stats['lines_covered'],
				$stats['lines_total'],
				$stats['methods_pct'],
				$stats['methods_covered'],
				$stats['methods_total'],
				$stats['classes_pct'],
				$stats['classes_covered'],
				$stats['classes_total'],
			)
			: 'No coverage data available (Xdebug/PCOV required)';

		$result = new CallToolResult(
			[new TextContent($summaryText)],
			structuredContent: [
				'ok' => $parsed['ok'],
				'tests_summary' => $parsed['summary'],
				'stats' => $stats,
				'files' => $files,
			],
		);

		$this->observer->log('test_coverage', ['filter' => $filter, 'maxPct' => $maxPct], $summaryText);

		return $result;
	}

	/**
	 * Runs PHPStan, PHPUnit, and PHP-CS-Fixer sequentially.
	 *
	 * Each tool runs as a separate child process. Output is capped per tool
	 * to avoid OOM in constrained containers. Only failed outputs are included
	 * in full; passing tools report only their ok/summary status.
	 *
	 * @param bool $fix when true, php-cs-fixer auto-fixes instead of just checking
	 */
	#[McpTool('check_code', 'Runs PHPStan + PHPUnit + PHP-CS-Fixer. Like `make check`. Set fix=true to auto-fix lint.')]
	public function checkCodeTool(bool $fix = false): CallToolResult
	{
		$phpstan = $this->shell->phpstanParsed();
		$phpstanOutput = $phpstan['ok'] ? '' : mb_substr($phpstan['output'], 0, self::CHECK_OUTPUT_CAP);
		unset($phpstan['output']);

		$phpunit = $this->shell->phpunitParsed();
		$phpunitOutput = $phpunit['ok'] ? '' : mb_substr($phpunit['output'], 0, self::CHECK_OUTPUT_CAP);
		unset($phpunit['output']);

		$lint = $this->shell->phpCsFixerParsed($fix);
		$lintOutput = $lint['ok'] ? '' : mb_substr($lint['output'], 0, self::CHECK_OUTPUT_CAP);
		unset($lint['output']);

		$allOk = $phpstan['ok'] && $phpunit['ok'] && $lint['ok'];
		$summaryText = ($allOk ? 'All checks passed' : 'Checks failed')
			. ' | phpstan:' . ($phpstan['ok'] ? 'ok' : 'fail')
			. ' phpunit:' . ($phpunit['ok'] ? 'ok' : 'fail')
			. ' lint:' . ($lint['ok'] ? 'ok' : 'fail');

		$structured = [
			'ok' => $allOk,
			'phpstan' => ['ok' => $phpstan['ok'], 'output' => $phpstanOutput],
			'phpunit' => ['ok' => $phpunit['ok'], 'summary' => $phpunit['summary'], 'output' => $phpunitOutput],
			'lint' => ['ok' => $lint['ok'], 'output' => $lintOutput],
		];

		$result = new CallToolResult(
			[new TextContent($summaryText)],
			isError: !$allOk,
			structuredContent: $structured,
		);

		$this->observer->log('check_code', ['fix' => $fix], $summaryText);

		return $result;
	}

	/**
	 * Returns today's cumulative MCP token usage summary.
	 */
	#[McpTool('token_usage', "Today's MCP token usage summary. Tracks I/O across all clients.")]
	public function tokenUsageTool(): CallToolResult
	{
		$summary = $this->observer->todaySummary();

		return new CallToolResult(
			[new TextContent($summary)],
			structuredContent: $summary,
		);
	}

	/**
	 * Runs a whitelisted ./sakoo sub-command.
	 *
	 * @param string $command sub-command with arguments (e.g. "composer info")
	 */
	#[McpTool('sakoo_exec', 'Runs a ./sakoo command. Allowed: assist, composer, npm.')]
	public function sakooExecTool(string $command): CallToolResult
	{
		try {
			['output' => $output] = $this->shell->sakoo($command);
		} catch (ExceptionInterface $e) {
			return CallToolResult::error([new TextContent($e->getMessage())]);
		}

		$this->observer->log('sakoo_exec', ['command' => $command], $output);

		return CallToolResult::success([new TextContent($output)]);
	}

	/**
	 * Exposes the full project file tree as an MCP resource.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('file://list')]
	public function getFilesListResource(): array
	{
		$path = Path::getRootDir() ?: __DIR__;
		$result = $this->getDirFileListTool($path);

		return $result->structuredContent ?? [];
	}

	/** Exposes the system prompt as a cacheable MCP resource. */
	#[McpResource('prompt://system')]
	public function systemPromptResource(): string
	{
		return $this->readReferenceFile('Skill/software-engineer.md');
	}

	/**
	 * Exposes the compact project structure as a cacheable resource.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('project://structure')]
	public function projectStructureResource(): array
	{
		$result = $this->projectStructureTool();

		return $result->structuredContent ?? [];
	}

	/**
	 * Application info (mode, environment, paths) as a cacheable resource.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('project://info')]
	public function applicationInfoResource(): array
	{
		$kernel = Kernel::getInstance();

		return [
			'mode' => $kernel->getMode()->value,
			'env' => $kernel->getEnvironment()->value,
			'replica' => $kernel->getReplicaId(),
			'paths' => [
				'root' => Path::getRootDir(),
				'storage' => Path::getStorageDir(),
				'logs' => Path::getLogsDir(),
				'vendor' => Path::getVendorDir(),
				'app' => Path::getAppDir(),
				'system' => Path::getSystemDir(),
			],
		];
	}

	/**
	 * Makefile targets as a cacheable resource.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('project://makefile')]
	public function makefileTargetsResource(): array
	{
		$path = Path::getRootDir() . '/Makefile';

		if (!is_file($path)) {
			return ['targets' => [], 'note' => 'no_makefile'];
		}

		return ['targets' => (new Makefile($path))->getTargets()];
	}

	/**
	 * Console commands as a cacheable resource.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('project://commands')]
	public function assistCommandsResource(): array
	{
		/** @var Application $application */
		$application = require Path::getAppDir() . '/Assist/Bootstrap.php';

		/** @var array<array{name: string, desc: string}> $list */
		$list = array_map(
			fn (Command $cmd): array => ['name' => $cmd::getName(), 'desc' => $cmd::getDescription()],
			$application->getCommands(),
		);

		return ['commands' => array_values($list)];
	}

	/** @see app/Assist/AI/Prompt/Reference/architecture-reference.md */
	#[McpResource('reference://architecture')]
	public function architectureReferenceResource(): string
	{
		return $this->readReferenceFile('Reference/architecture-reference.md');
	}

	/** @see app/Assist/AI/Prompt/Reference/coding-conventions.md */
	#[McpResource('reference://conventions')]
	public function conventionsResource(): string
	{
		return $this->readReferenceFile('Reference/coding-conventions.md');
	}

	/** @see app/Assist/AI/Prompt/Reference/sakoo-identity.md */
	#[McpResource('reference://sakoo-identity')]
	public function sakooIdentityResource(): string
	{
		return $this->readReferenceFile('Reference/sakoo-identity.md');
	}

	/** @see app/Assist/AI/Prompt/Reference/prompt-engineering.md */
	#[McpResource('reference://prompt-engineering')]
	public function promptEngineeringResource(): string
	{
		return $this->readReferenceFile('Reference/prompt-engineering.md');
	}

	/** @see app/Assist/AI/Prompt/Reference/quality-assurance.md */
	#[McpResource('reference://quality-assurance')]
	public function qualityAssuranceResource(): string
	{
		return $this->readReferenceFile('Reference/quality-assurance.md');
	}

	/** @see app/Assist/AI/Prompt/Reference/file-handling.md */
	#[McpResource('reference://file-handling')]
	public function fileHandlingResource(): string
	{
		return $this->readReferenceFile('Reference/file-handling.md');
	}

	/**
	 * Builds a two-message prompt (system + user) from stored Markdown files.
	 *
	 * @param string $fileName relative path under Assist/AI/Prompt/
	 *
	 * @return PromptMessage[]
	 *
	 * @throws InvalidArgumentException when the prompt file path escapes the project scope
	 */
	#[McpPrompt('dev_task')]
	public function devTaskPrompt(string $fileName): array
	{
		McpPathGuard::guard(Path::getAppDir() . "/Assist/AI/Prompt/{$fileName}");

		/** @var string[] $systemPrompt */
		$systemPrompt = File::open(Disk::Local, Path::getAppDir() . '/Assist/AI/Prompt/Skill/software-engineer.md')->readLines();

		/** @var string[] $userPrompt */
		$userPrompt = File::open(Disk::Local, Path::getAppDir() . "/Assist/AI/Prompt/{$fileName}")->readLines();

		return [
			new PromptMessage(Role::User, new TextContent(["[System context]\n" . implode(PHP_EOL, $systemPrompt)])),
			new PromptMessage(Role::User, new TextContent($userPrompt)),
		];
	}

	/**
	 * Returns a minimal prompt for a single-file code review.
	 *
	 * @param string $path path to the file to review (scoped to project)
	 *
	 * @return PromptMessage[]
	 *
	 * @throws InvalidArgumentException when the path escapes the project scope
	 */
	#[McpPrompt('review_file')]
	public function reviewFilePrompt(string $path): array
	{
		$path = McpPathGuard::guard($path);
		Assert::file($path, "Not a file: {$path}");

		/** @var string[] $lines */
		$lines = File::open(Disk::Local, $path)->readLines();
		$content = implode(PHP_EOL, $lines);
		$tokens = (new McpTokenCalculator())->countText($content);

		return [
			new PromptMessage(
				Role::User,
				new TextContent(["Review this file ({$tokens} tokens):\n\n```php\n{$content}\n```"])
			),
		];
	}

	/**
	 * Reads a reference Markdown file from the Prompt directory.
	 *
	 * @param string $relativePath path relative to Assist/AI/Prompt/
	 */
	private function readReferenceFile(string $relativePath): string
	{
		/** @var string[] $lines */
		$lines = File::open(Disk::Local, Path::getAppDir() . '/Assist/AI/Prompt/' . $relativePath)->readLines();

		return implode(PHP_EOL, $lines);
	}
}
