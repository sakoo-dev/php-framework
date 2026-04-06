<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ExceptionInterface;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Mcp\Schema\Result\CallToolResult;
use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
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
 * @see FileFinder::guard()    Path-traversal protection.
 * @see McpShell               Project-scoped shell executor.
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

	private McpShell $shell;
	private McpTokenObserver $observer;

	public function __construct()
	{
		$this->shell = resolve(McpShell::class);
		$this->observer = resolve(McpTokenObserver::class);
	}

	#[McpTool('read_file', 'Reads file content. Use `from`/`to` for line ranges, `maxChars` to cap size. Scoped to project.')]
	public function readFileTool(string $path, int $from = 1, int $to = 0, int $maxChars = 50000): CallToolResult
	{
		$path = FileFinder::guard($path);
		$input = compact('path', 'from', 'to', 'maxChars');

		Assert::lazy()
			->file($path, "Not a file: {$path}")
			->notDir($path, "Is a directory: {$path}")
			->validate();

		$chunk = File::open(Disk::Local, $path)->readChunk($from, $to, $maxChars);

		$result = new CallToolResult(
			[new TextContent($chunk->content)],
			structuredContent: ['path' => $path, 'from' => $chunk->from, 'to' => $chunk->to, 'totalLines' => $chunk->totalLines, 'truncated' => $chunk->truncated],
			meta: ['truncated' => $chunk->truncated],
		);

		$this->observer->log('read_file', $input, $chunk->content);

		return $result;
	}

	#[McpTool('write_file', 'Writes content to a file. Scoped to project.')]
	public function writeFileTool(string $path, string $content): CallToolResult
	{
		$path = FileFinder::guard($path);
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

	#[McpTool('remove_file', 'Deletes a file from the project. Scoped to project. Refuses directories.')]
	public function removeFileTool(string $path): CallToolResult
	{
		$path = FileFinder::guard($path);

		if (is_dir($path)) {
			return CallToolResult::error([new TextContent("Is a directory: {$path}")]);
		}

		if (!is_file($path)) {
			return CallToolResult::error([new TextContent("Not found: {$path}")]);
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
	 * @param string[] $paths
	 */
	#[McpTool('read_files', 'Reads multiple files. Returns {path: content} map. Use maxChars to limit per-file output.')]
	public function readFilesTool(array $paths, int $maxChars = 30000): CallToolResult
	{
		$paths = FileFinder::guardMany($paths);
		$files = [];
		$errors = [];

		foreach ($paths as $path) {
			if (!is_file($path) || !is_readable($path)) {
				$errors[$path] = 'not_readable';

				continue;
			}

			$files[$path] = File::open(Disk::Local, $path)->readChunkText(maxChars: $maxChars);
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

	#[McpTool('dir_files', 'Lists files in a directory. Use pattern/limit to control scope. Scoped to project.')]
	public function getDirFileListTool(string $path, string $pattern = '', int $limit = 500): CallToolResult
	{
		$path = FileFinder::guard($path);

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

	#[McpTool('browser_logs', 'Returns latest HTTP VarDump entries. Use limit to control size.')]
	public function browserLogsTool(int $limit = 50): CallToolResult
	{
		$logFile = Path::getStorageDir() . '/browser/http.log';
		$file = File::open(Disk::Local, $logFile);

		if (!$file->exists()) {
			return CallToolResult::success([new TextContent('No browser log found.')], ['note' => 'no_log']);
		}

		$entries = $file->readTail($limit)->lines;

		$result = new CallToolResult(
			[new TextContent($entries)],
			structuredContent: ['entries' => $entries],
		);

		$this->observer->log('browser_logs', ['limit' => $limit], $entries);

		return $result;
	}

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

		$entries = $file->readTail($limit)->lines;

		$result = new CallToolResult(
			[new TextContent($entries)],
			structuredContent: ['entries' => $entries],
			meta: ['date' => $date, 'n' => count($entries)],
		);

		$this->observer->log('read_log_entries', compact('date', 'limit'), $entries);

		return $result;
	}

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

		$lines = $file->readLines();
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

	#[McpTool('get_absolute_url', 'Resolves absolute URL for a path using APP_URL.')]
	public function getAbsoluteUrlTool(string $path = '/'): CallToolResult
	{
		/** @var string $appUrl */
		$appUrl = Env::get('APP_URL', 'http://localhost');
		$url = rtrim($appUrl, '/') . '/' . ltrim($path, '/');

		return CallToolResult::success([new TextContent($url)]);
	}

	#[McpTool('git_log', 'Recent git commits as {hash, msg}. Use limit and path to narrow.')]
	public function gitLogTool(int $limit = 20, string $path = ''): CallToolResult
	{
		$this->guardOptionalPath($path);

		$parsed = $this->shell->gitLogParsed($limit, $path);

		$result = new CallToolResult(
			[new TextContent($parsed['commits'])],
			structuredContent: ['commits' => $parsed['commits']],
			meta: ['n' => count($parsed['commits'])],
		);

		$this->observer->log('git_log', compact('limit', 'path'), $parsed['commits']);

		return $result;
	}

	#[McpTool('git_diff', 'Git diff output. Defaults to unstaged. Use ref, path, maxLines to control.')]
	public function gitDiffTool(string $ref = '', string $path = '', int $maxLines = 200): CallToolResult
	{
		$this->guardOptionalPath($path);

		$parsed = $this->shell->gitDiffParsed($ref, $path, $maxLines);

		$result = new CallToolResult(
			[new TextContent($parsed['diff'])],
			structuredContent: ['diff' => $parsed['diff']],
			meta: ['lines' => $parsed['total'], 'truncated' => $parsed['truncated']],
		);

		$this->observer->log('git_diff', compact('ref', 'path', 'maxLines'), $parsed['diff']);

		return $result;
	}

	#[McpTool('git_status', 'Shows uncommitted changes (staged, unstaged, untracked). Use before committing.')]
	public function gitStatusTool(string $path = ''): CallToolResult
	{
		$this->guardOptionalPath($path);

		$parsed = $this->shell->gitStatusParsed($path);

		$result = new CallToolResult(
			[new TextContent($parsed['summary'])],
			structuredContent: ['clean' => $parsed['clean'], 'files' => $parsed['files']],
			meta: ['summary' => $parsed['summary']],
		);

		$this->observer->log('git_status', ['path' => $path], $parsed['files']);

		return $result;
	}

	#[McpTool('test_run', 'Runs PHPUnit tests. Optional filter. Returns pass/fail.')]
	public function testRunTool(string $filter = ''): CallToolResult
	{
		$parsed = $this->shell->phpunitParsed($filter);

		$result = new CallToolResult(
			[new TextContent($parsed['output'])],
			isError: !$parsed['ok'],
			structuredContent: ['ok' => $parsed['ok'], 'summary' => $parsed['summary'], 'exitCode' => $parsed['exitCode']],
		);

		$this->observer->log('test_run', ['filter' => $filter], $parsed['output']);

		return $result;
	}

	#[McpTool('test_coverage', 'Runs PHPUnit with code coverage. Returns line/method/class stats and uncovered lines per file, sorted worst first.')]
	public function testCoverageTool(string $filter = '', int $maxPct = 100): CallToolResult
	{
		$parsed = $this->shell->phpunitCoverageParsed($filter);
		$stats = $parsed['stats'];
		$files = $this->filterCoverageFiles($parsed['files'], $maxPct);
		$summaryText = $this->formatCoverageSummary($stats);

		$result = new CallToolResult(
			[new TextContent($summaryText)],
			structuredContent: [
				'ok' => $parsed['ok'],
				'tests_summary' => $parsed['summary'],
				'exitCode' => $parsed['exitCode'],
				'stats' => $stats,
				'files' => $files,
			],
		);

		$this->observer->log('test_coverage', ['filter' => $filter, 'maxPct' => $maxPct], $summaryText);

		return $result;
	}

	#[McpTool('check_code', 'Runs PHPStan + PHPUnit + PHP-CS-Fixer. Like `make check`. Set fix=true to auto-fix lint.')]
	public function checkCodeTool(bool $fix = false): CallToolResult
	{
		$phpstan = $this->compactCheckResult($this->shell->phpstanParsed());
		$phpunit = $this->compactCheckResult($this->shell->phpunitParsed());
		$lint = $this->compactCheckResult($this->shell->phpCsFixerParsed($fix));

		$allOk = $phpstan['ok'] && $phpunit['ok'] && $lint['ok'];
		$summaryText = sprintf(
			'%s | phpstan:%s phpunit:%s lint:%s',
			$allOk ? 'All checks passed' : 'Checks failed',
			$phpstan['ok'] ? 'ok' : 'fail',
			$phpunit['ok'] ? 'ok' : 'fail',
			$lint['ok'] ? 'ok' : 'fail',
		);

		$structured = [
			'ok' => $allOk,
			'phpstan' => $phpstan,
			'phpunit' => $phpunit,
			'lint' => $lint,
		];

		$result = new CallToolResult(
			[new TextContent($summaryText)],
			isError: !$allOk,
			structuredContent: $structured,
		);

		$this->observer->log('check_code', ['fix' => $fix], $summaryText);

		return $result;
	}

	#[McpTool('token_usage', "Today's MCP token usage summary. Tracks I/O across all clients.")]
	public function tokenUsageTool(): CallToolResult
	{
		$summary = $this->observer->todaySummary();

		return new CallToolResult(
			[new TextContent($summary)],
			structuredContent: $summary,
		);
	}

	#[McpTool('sakoo_exec', 'Runs a ./sakoo command. Allowed: assist, composer, npm.')]
	public function sakooExecTool(string $command): CallToolResult
	{
		try {
			$result = $this->shell->sakoo($command);
		} catch (ExceptionInterface $e) {
			return CallToolResult::error([new TextContent($e->getMessage())]);
		}

		$output = $result['output'];
		$exitCode = $result['exitCode'];

		$this->observer->log('sakoo_exec', ['command' => $command], $output);

		return new CallToolResult(
			[new TextContent($output)],
			isError: 0 !== $exitCode,
			structuredContent: ['exitCode' => $exitCode],
		);
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('file://list')]
	public function getFilesListResource(): array
	{
		$path = Path::getRootDir() ?: __DIR__;
		$result = $this->getDirFileListTool($path);

		return $result->structuredContent ?? [];
	}

	#[McpResource('prompt://system')]
	public function systemPromptResource(): string
	{
		return $this->readReferenceFile('Skill/software-engineer.md');
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource('project://structure')]
	public function projectStructureResource(): array
	{
		$result = $this->projectStructureTool();

		return $result->structuredContent ?? [];
	}

	/**
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
	 * @param string $fileName relative path under Assist/AI/Prompt/
	 *
	 * @return PromptMessage[]
	 */
	#[McpPrompt('dev_task')]
	public function devTaskPrompt(string $fileName): array
	{
		$userPromptPath = $this->resolvePromptFilePath($fileName);
		$systemPrompt = File::open(Disk::Local, Path::getAppDir() . '/Assist/AI/Prompt/Skill/software-engineer.md')->readLines();
		$userPrompt = File::open(Disk::Local, $userPromptPath)->readLines();

		return [
			new PromptMessage(Role::User, new TextContent(["[System context]\n" . implode(PHP_EOL, $systemPrompt)])),
			new PromptMessage(Role::User, new TextContent($userPrompt)),
		];
	}

	/**
	 * @param string $path path to the file to review (scoped to project)
	 *
	 * @return PromptMessage[]
	 */
	#[McpPrompt('review_file')]
	public function reviewFilePrompt(string $path): array
	{
		$path = FileFinder::guard($path);
		Assert::file($path, "Not a file: {$path}");

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
	 * Guards an optional path when provided.
	 */
	private function guardOptionalPath(string $path): void
	{
		if ('' !== $path) {
			FileFinder::guard($path);
		}
	}

	/**
	 * Resolves a dev_task prompt file and guarantees it stays under Assist/AI/Prompt.
	 *
	 * @throws InvalidArgumentException
	 */
	private function resolvePromptFilePath(string $fileName): string
	{
		$promptBase = FileFinder::guard(Path::getAppDir() . '/Assist/AI/Prompt');
		$resolved = FileFinder::guard($promptBase . '/' . ltrim($fileName, '/'));
		$prefix = rtrim($promptBase, '/') . '/';

		if (!str_starts_with($resolved, $prefix)) {
			throw new InvalidArgumentException("Prompt path escapes Assist/AI/Prompt: {$fileName}");
		}

		return $resolved;
	}

	/**
	 * @param array{ok: bool, output: string, exitCode: int, summary?: string} $result
	 *
	 * @return array{ok: bool, output: string, exitCode: int, summary?: string}
	 */
	private function compactCheckResult(array $result): array
	{
		if ($result['ok']) {
			$result['output'] = '';

			return $result;
		}

		$result['output'] = mb_substr($result['output'], 0, self::CHECK_OUTPUT_CAP);

		return $result;
	}

	/**
	 * @param array<int, array<string, mixed>> $files
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function filterCoverageFiles(array $files, int $maxPct): array
	{
		if ($maxPct >= 100) {
			return $files;
		}

		return array_values(array_filter($files, static fn (array $f): bool => $f['lines_pct'] < $maxPct));
	}

	/**
	 * @param array<string, mixed> $stats
	 */
	private function formatCoverageSummary(array $stats): string
	{
		if ([] === $stats) {
			return 'No coverage data available (Xdebug/PCOV required)';
		}

		return sprintf(
			'Coverage — Lines: %.2f%% (%d/%d) | Methods: %.2f%% (%d/%d) | Classes: %.2f%% (%d/%d)',
			$this->toFloatStat($stats['lines_pct'] ?? null),
			$this->toIntStat($stats['lines_covered'] ?? null),
			$this->toIntStat($stats['lines_total'] ?? null),
			$this->toFloatStat($stats['methods_pct'] ?? null),
			$this->toIntStat($stats['methods_covered'] ?? null),
			$this->toIntStat($stats['methods_total'] ?? null),
			$this->toFloatStat($stats['classes_pct'] ?? null),
			$this->toIntStat($stats['classes_covered'] ?? null),
			$this->toIntStat($stats['classes_total'] ?? null),
		);
	}

	private function toFloatStat(mixed $value): float
	{
		if (is_int($value) || is_float($value)) {
			return (float) $value;
		}

		if (is_string($value) && is_numeric($value)) {
			return (float) $value;
		}

		return 0.0;
	}

	private function toIntStat(mixed $value): int
	{
		if (is_int($value)) {
			return $value;
		}

		if (is_float($value)) {
			return (int) round($value);
		}

		if (is_string($value) && is_numeric($value)) {
			return (int) round((float) $value);
		}

		return 0;
	}

	private function readReferenceFile(string $relativePath): string
	{
		$lines = File::open(Disk::Local, Path::getAppDir() . '/Assist/AI/Prompt/' . $relativePath)->readLines();

		return implode(PHP_EOL, $lines);
	}
}
