<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
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
 * Design contract:
 *   - Tools    → LLM selects and invokes (prefer for dynamic/stateful data).
 *   - Resources → User attaches explicitly (prefer for static/reusable context).
 *   - Prompts  → User runs as template shortcuts.
 *
 * Token-efficiency rules applied throughout:
 *   - Short JSON keys on all tool responses.
 *   - No redundant prose in return values.
 *   - Resources used for stable data so Claude Desktop can cache them.
 *   - Prompts compose system + user from pre-authored Markdown files.
 *   - Static/semi-static data exposed as Resources, not Tools — reduces tool-selection overhead.
 */
class McpElements
{
	//	#[McpTool('rhel_integration', 'Returns Interactive Shell on the Project')]
	//	#[McpTool('route_inspection', 'Scans the project for route definition files and returns their contents.')]
	//	#[McpTool('database_connections', 'Lists all configured database connections read from environment variables (DB_*).')]
	//	#[McpTool('database_query', 'Executes a read-only SQL query against the default PDO connection and returns results.')]
	//	#[McpTool('database_schema', 'Returns the schema (tables and columns) of the configured database.')]

	#[McpTool('read_file', 'Reads content of a file in the Sakoo PHP Framework')]
	public function readFileTool(string $path): string
	{
		Assert::lazy()
			->file($path, "Not a file: {$path}")
			->notDir($path, "Is a directory: {$path}")
			->validate();

		/** @var string[] $lines */
		$lines = File::open(Disk::Local, $path)->readLines();
		Assert::array($lines, "Cannot read: {$path}");

		return implode(PHP_EOL, $lines);
	}

	#[McpTool('write_file', 'Writes content in a new file in the Sakoo PHP Framework')]
	public function writeFileTool(string $path, string $content): string
	{
		$stored = File::open(Disk::Local, $path)->write($content);

		return $stored ? "ok:{$path}" : "fail:{$path}";
	}

	/** @return array<string,string[]> */
	#[McpTool('dir_files', 'Extracts files from input directory.')]
	public function getDirFileListTool(string $path): array
	{
		$files = (new FileFinder($path))
			->ignoreDotFiles()
			->ignoreVCS()
			->ignoreVCSIgnored()
			->find();

		return ['files' => $files];
	}

	/**
	 * Token-efficient alternative to list_files.
	 * Returns only app/ and system/ and core/ trees, grouped by module, with vendor excluded.
	 * Use this before read_file to locate relevant files without loading all paths.
	 *
	 * @return array{app: string[], core: string[], system: string[]}
	 */
	#[McpTool('project_structure', 'Returns compact app/ and system/ and core/ file trees (no vendor). Use before read_file to locate files.')]
	public function projectStructureTool(): array
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

		return [
			'app' => $appFiles,
			'core' => $coreFiles,
			'system' => $systemFiles,
		];
	}

	/**
	 * Reads multiple files in one call, returning a map of path → content.
	 * Eliminates N sequential read_file round-trips; reduces total tool-call overhead.
	 *
	 * @param string[] $paths
	 *
	 * @return array<string, string>
	 */
	#[McpTool('read_files', 'Reads multiple files in one call. Pass an array of absolute paths. Returns {path: content} map.')]
	public function readFilesTool(array $paths): array
	{
		$result = [];

		foreach ($paths as $path) {
			if (!is_file($path) || !is_readable($path)) {
				$result[$path] = 'err:not_readable';

				continue;
			}

			/** @var false|string[] $lines */
			$lines = File::open(Disk::Local, $path)->readLines();
			$result[$path] = is_array($lines) ? implode(PHP_EOL, $lines) : 'err:read_failed';
		}

		return $result;
	}

	/** @phpstan-ignore missingType.iterableValue */
	#[McpTool('browser_logs', "Returns the latest HTTP VarDump entries stored by the framework's HttpDumper.")]
	public function browserLogsTool(int $limit = 50): array
	{
		$logFile = Path::getStorageDir() . '/browser/http.log';
		$file = File::open(Disk::Local, $logFile);

		if (!$file->exists()) {
			return ['entries' => [], 'note' => 'no_log'];
		}

		/** @var string[] $lines */
		$lines = array_filter((array) $file->readLines());

		return ['entries' => array_slice(array_reverse($lines), 0, $limit)];
	}

	/** @phpstan-ignore missingType.iterableValue  */
	#[McpTool('read_log_entries', 'Reads log entries from the framework log file. Optionally filter by date (Y/m/d) and limit results.')]
	public function readLogEntriesTool(string $date = '', int $limit = 100): array
	{
		if ('' === $date) {
			$date = date('Y/m/d');
		}

		$logPath = Path::getLogsDir() . '/' . $date . '.log';
		$file = File::open(Disk::Local, $logPath);

		if (!$file->exists()) {
			return ['entries' => [], 'note' => "no_log:{$date}"];
		}

		/** @var string[] $lines */
		$lines = array_filter((array) $file->readLines());
		$entries = array_slice(array_reverse($lines), 0, $limit);

		return ['date' => $date, 'n' => count($entries), 'entries' => $entries];
	}

	/** @phpstan-ignore missingType.iterableValue  */
	#[McpTool('last_error', 'Returns the last PHP error recorded via error_get_last().')]
	public function lastErrorTool(): array
	{
		$error = error_get_last();

		if (null === $error) {
			return ['error' => null];
		}

		return [
			'type' => $error['type'],
			'msg' => $error['message'],
			'file' => $error['file'],
			'line' => $error['line'],
		];
	}

	/** @phpstan-ignore missingType.iterableValue */
	#[McpTool('search_docs', 'Searches the generated wiki documentation for a given keyword and returns matching lines.')]
	public function searchDocsTool(string $keyword, int $limit = 30): array
	{
		$wikiFile = Path::getRootDir() . '/.github/wiki/Home.md';
		$file = File::open(Disk::Local, $wikiFile);

		if (!$file->exists()) {
			return ['matches' => [], 'note' => 'run: php assist doc:gen'];
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

		return ['kw' => $keyword, 'n' => count($matches), 'matches' => $matches];
	}

	#[McpTool('get_absolute_url', 'Resolves an absolute URL for a given path using APP_URL from the environment.')]
	public function getAbsoluteUrlTool(string $path = '/'): string
	{
		/** @var string $appUrl */
		$appUrl = Env::get('APP_URL', 'http://localhost');

		return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
	}

	/**
	 * Structured git log with separated hash and message.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpTool('git_log', 'Returns recent git commits as {hash, msg} objects. Optionally limit count and filter by path.')]
	public function gitLogTool(int $limit = 20, string $path = ''): array
	{
		$root = Path::getRootDir();
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';

		/** @phpstan-ignore sakoo.vulnerability.dangerousFunctions */
		$output = (string) shell_exec(
			"git -C {$root} log --format='%h|%s' --no-decorate -n {$limit}{$pathArg} 2>&1"
		);

		$lines = array_filter(explode("\n", trim($output)));

		$commits = array_map(static function (string $line): array {
			$parts = explode('|', $line, 2);

			return ['hash' => $parts[0], 'msg' => $parts[1] ?? ''];
		}, $lines);

		return ['n' => count($commits), 'commits' => $commits];
	}

	/**
	 * Bounded git diff with truncation to prevent context blowout.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpTool('git_diff', 'Returns git diff output. Defaults to unstaged changes. Pass ref to diff against a commit/branch. Truncates at maxLines.')]
	public function gitDiffTool(string $ref = '', string $path = '', int $maxLines = 200): array
	{
		$root = Path::getRootDir();
		$refArg = '' !== $ref ? ' ' . escapeshellarg($ref) : '';
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$raw = trim((string) shell_exec(
			"git -C {$root} diff{$refArg}{$pathArg} 2>&1"
		));

		$lines = explode("\n", $raw);
		$total = count($lines);
		$truncated = $total > $maxLines;

		if ($truncated) {
			$lines = array_slice($lines, 0, $maxLines);
		}

		return [
			'diff' => implode("\n", $lines),
			'lines' => $total,
			'truncated' => $truncated,
		];
	}

	/**
	 * Structured test runner with parsed pass/fail output.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpTool('test_run', 'Runs PHPUnit tests. Optional filter for specific test. Returns structured pass/fail results.')]
	public function testRunTool(string $filter = ''): array
	{
		$root = Path::getRootDir();
		$filterArg = '' !== $filter ? ' --filter=' . escapeshellarg($filter) : '';

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$output = trim((string) shell_exec(
			"cd {$root} && php vendor/bin/phpunit{$filterArg} --no-progress --colors=never 2>&1"
		));

		$lines = explode("\n", $output);
		$summary = '';
		$ok = false;

		foreach (array_reverse($lines) as $line) {
			if (preg_match('/^(OK|FAILURES|ERRORS|Tests:)/', trim($line))) {
				$summary = trim($line);
				$ok = str_starts_with($summary, 'OK');

				break;
			}
		}

		return [
			'ok' => $ok,
			'summary' => $summary,
			'output' => $output,
		];
	}

	#[McpTool('sakoo_exec', 'Runs a ./sakoo command (docker proxy). E.g. "composer info", "assist doc:gen".')]
	public function sakooExecTool(string $command): string
	{
		$root = Path::getRootDir();
		$allowed = ['assist', 'composer', 'npm'];
		$parts = explode(' ', trim($command), 2);
		$sub = $parts[0];

		if (!in_array($sub, $allowed, true)) {
			return 'err:denied. Allowed sub-commands: ' . implode(', ', $allowed);
		}

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		return trim((string) shell_exec(
			"cd {$root} && " . escapeshellcmd($command) . ' 2>&1'
		));
	}

	/**
	 * Exposes the full project file tree as an MCP resource.
	 *
	 * @return array<string,string[]>
	 */
	#[McpResource('file://list')]
	public function getFilesListResource(): array
	{
		$path = Path::getRootDir() ?: __DIR__;

		return $this->getDirFileListTool($path);
	}

	/**
	 * Exposes the system prompt as a cacheable MCP resource.
	 * Attach once per session instead of re-sending ~500 tokens on every message.
	 */
	#[McpResource('prompt://system')]
	public function systemPromptResource(): string
	{
		return $this->readReferenceFile('Skill/software-engineer.md');
	}

	/**
	 * Exposes the compact project structure (app/ + system/ + core/ only) as a cacheable resource.
	 *
	 * @return array{app: string[], core: string[], system: string[]}
	 */
	#[McpResource('project://structure')]
	public function projectStructureResource(): array
	{
		return $this->projectStructureTool();
	}

	/**
	 * App info (mode, env, paths) as a cacheable resource — doesn't change during a session.
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
	 * Makefile targets as a cacheable resource — rarely changes during a session.
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
	 * Console commands as a cacheable resource — doesn't change during a session.
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

	/** Architecture patterns: SOLID, DDD, Hexagonal, CQRS, Security, Performance, Testing. */
	#[McpResource('reference://architecture')]
	public function architectureReferenceResource(): string
	{
		return $this->readReferenceFile('Reference/architecture-reference.md');
	}

	/** Sakoo PHP coding conventions: file structure, types, naming, collections, DI, docs. */
	#[McpResource('reference://conventions')]
	public function conventionsResource(): string
	{
		return $this->readReferenceFile('Reference/coding-conventions.md');
	}

	/** Sakoo framework identity: value propositions, core components, system layer. */
	#[McpResource('reference://sakoo-identity')]
	public function sakooIdentityResource(): string
	{
		return $this->readReferenceFile('Reference/sakoo-identity.md');
	}

	/** Prompt engineering: skill architecture, token efficiency, MCP element design, anti-patterns. */
	#[McpResource('reference://prompt-engineering')]
	public function promptEngineeringResource(): string
	{
		return $this->readReferenceFile('Reference/prompt-engineering.md');
	}

	/** QA & verification: code review checklist, test writing, visual QA, document QA. */
	#[McpResource('reference://quality-assurance')]
	public function qualityAssuranceResource(): string
	{
		return $this->readReferenceFile('Reference/quality-assurance.md');
	}

	/** File handling: reading strategies by type, PDF/XLSX/DOCX/PPTX processing, common pitfalls. */
	#[McpResource('reference://file-handling')]
	public function fileHandlingResource(): string
	{
		return $this->readReferenceFile('Reference/file-handling.md');
	}

	/**
	 * Builds a two-message prompt (system + user) from a stored Markdown prompt file.
	 *
	 * @return PromptMessage[]
	 */
	#[McpPrompt('dev_task')]
	public function devTaskPrompt(string $fileName): array
	{
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
	 * @return PromptMessage[]
	 */
	#[McpPrompt('review_file')]
	public function reviewFilePrompt(string $path): array
	{
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

	private function readReferenceFile(string $relativePath): string
	{
		/** @var string[] $lines */
		$lines = File::open(Disk::Local, Path::getAppDir() . '/Assist/AI/Prompt/' . $relativePath)->readLines();

		return implode(PHP_EOL, $lines);
	}
}
