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
use Sakoo\Framework\Core\Kernel\Kernel;
use System\Path\Path;

/**
 * - Tool: LLM Selects to use it, with Permission request
 * - Resource / Resource Template: User Selects to use it in their command
 * - Prompt: User runs as a Prompt Shortcut / Template.
 *
 * - Implement elements to work with assist, database schema, docs, ...
 */
class McpElements
{
	#[McpTool('read_file', 'Reads content of a file in the Sakoo PHP Framework')]
	public function readFileTool(string $path): string
	{
		$lines = File::open(Disk::Local, $path)->readLines();
		Assert::array($lines, "The path '{$path}' does not exist.");

		// @phpstan-ignore argument.type
		return implode(PHP_EOL, $lines);
	}

	#[McpTool('write_file', 'Writes content in a new file in the Sakoo PHP Framework')]
	public function writeFileTool(string $path, string $content): string
	{
		if (File::open(Disk::Local, $path)->write($content)) {
			return 'File stored successfully at: ' . $path;
		}

		return 'Failed to store file at: ' . $path;
	}

	/** @phpstan-ignore-next-line */
	#[McpTool('list_files', 'Extracts all of project files.')]
	public function getFilesListTool(): array
	{
		return $this->getFilesListResource();
	}

	/**
	 * @return string[]
	 */
	#[McpResource('file://list')]
	public function getFilesListResource(): array
	{
		$path = Path::getRootDir() ?: __DIR__;

		return (new FileFinder($path))
			->ignoreDotFiles()
			->ignoreVCS()
			->ignoreVCSIgnored()
			->find();
	}

	#[McpPrompt('feature_from_story')]
	public function featureFromStory(string $fileName): PromptMessage
	{
		$promptPath = __DIR__ . "../Prompt/$fileName";
		$systemPath = __DIR__ . '../Prompt/00-system-prompt.md';

		$prompt = "Create a sample and short code based on $promptPath "
			. "file contents with conditions written in $systemPath file."
			. 'then create a file with project naming convention (PSR-4) and store it.';

		return new PromptMessage(Role::User, new TextContent($prompt));
	}

	/** @phpstan-ignore-next-line */
	#[McpTool('application_info', 'Returns general Sakoo application info: mode, environment, replica ID, and key paths.')]
	public function applicationInfoTool(): array
	{
		$kernel = Kernel::getInstance();

		return [
			'mode' => $kernel->getMode()->value,
			'environment' => $kernel->getEnvironment()->value,
			'replica_id' => $kernel->getReplicaId(),
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

	/** @phpstan-ignore-next-line */
	#[McpTool('browser_logs', 'Returns the latest HTTP VarDump entries stored by the framework\'s HttpDumper.')]
	public function browserLogsTool(int $limit = 50): array
	{
		$logFile = Path::getStorageDir() . '/browser/http.log';
		$file = File::open(Disk::Local, $logFile);

		if (!$file->exists()) {
			return [
				'entries' => [],
				'note' => 'No browser dump log found.',
			];
		}

		/** @var string[] $lines */
		$lines = array_filter((array) $file->readLines());
		$entries = array_slice(array_reverse($lines), 0, $limit);

		return ['entries' => $entries];
	}

	//	#[McpTool('database_connections', 'Lists all configured database connections read from environment variables (DB_*).')]
	//	public function databaseConnectionsTool(): string
	//	{
	//		$connections = [];
	//
	//		foreach ($_ENV as $key => $value) {
	//			if (str_starts_with($key, 'DB_')) {
	//				$connections[$key] = str_contains(strtolower($key), 'pass') ? '***' : $value;
	//			}
	//		}
	//
	//		if (empty($connections)) {
	//			return json_encode(['connections' => [], 'note' => 'No DB_* environment variables found.']);
	//		}
	//
	//		return json_encode(['connections' => $connections], JSON_PRETTY_PRINT) ?: '{}';
	//	}

	//	#[McpTool('database_query', 'Executes a read-only SQL query against the default PDO connection and returns results.')]
	//	public function databaseQueryTool(string $sql): string
	//	{
	//		if (!preg_match('/^\s*SELECT\b/i', $sql)) {
	//			return json_encode(['error' => 'Only SELECT queries are allowed.']);
	//		}
	//
	//		try {
	//			$dsn = sprintf(
	//				'%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
	//				Env::get('DB_DRIVER', 'mysql'),
	//				Env::get('DB_HOST', '127.0.0.1'),
	//				Env::get('DB_PORT', '3306'),
	//				Env::get('DB_DATABASE', ''),
	//			);
	//			$pdo = new \PDO($dsn, Env::get('DB_USERNAME'), Env::get('DB_PASSWORD'), [
	//				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	//			]);
	//			$stmt = $pdo->query($sql);
	//			$rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	//
	//			return json_encode(['rows' => $rows, 'count' => count($rows)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
	//		} catch (\Throwable $e) {
	//			return json_encode(['error' => $e->getMessage()]);
	//		}
	//	}

	//	#[McpTool('database_schema', 'Returns the schema (tables and columns) of the configured database.')]
	//	public function databaseSchemaTool(?string $table = null): string
	//	{
	//		try {
	//			$dsn = sprintf(
	//				'%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
	//				Env::get('DB_DRIVER', 'mysql'),
	//				Env::get('DB_HOST', '127.0.0.1'),
	//				Env::get('DB_PORT', '3306'),
	//				Env::get('DB_DATABASE', ''),
	//			);
	//			$pdo = new \PDO($dsn, Env::get('DB_USERNAME'), Env::get('DB_PASSWORD'), [
	//				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	//			]);
	//
	//			if ($table) {
	//				$stmt = $pdo->query("DESCRIBE `$table`");
	//				$columns = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
	//
	//				return json_encode(['table' => $table, 'columns' => $columns], JSON_PRETTY_PRINT) ?: '{}';
	//			}
	//
	//			$stmt = $pdo->query('SHOW TABLES');
	//			$tables = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
	//			$schema = [];
	//
	//			foreach ($tables as $tbl) {
	//				$s = $pdo->query("DESCRIBE `$tbl`");
	//				$schema[$tbl] = $s ? $s->fetchAll(\PDO::FETCH_ASSOC) : [];
	//			}
	//
	//			return json_encode(['schema' => $schema], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
	//		} catch (\Throwable $e) {
	//			return json_encode(['error' => $e->getMessage()]);
	//		}
	//	}

	#[McpTool('get_absolute_url', 'Resolves an absolute URL for a given path using APP_URL from the environment.')]
	public function getAbsoluteUrlTool(string $path = '/'): string
	{
		/** @var string $url */
		$url = Env::get('APP_URL', 'http://localhost');
		$base = rtrim($url, '/');
		$path = '/' . ltrim($path, '/');

		return $base . $path;
	}

	/** @phpstan-ignore-next-line */
	#[McpTool('last_error', 'Returns the last PHP error recorded via error_get_last().')]
	public function lastErrorTool(): array
	{
		$error = error_get_last();

		if (!$error) {
			return [
				'error' => null,
				'note' => 'No error recorded in this process.',
			];
		}

		return [
			'type' => $error['type'],
			'message' => $error['message'],
			'file' => $error['file'],
			'line' => $error['line'],
		];
	}

	/** @phpstan-ignore-next-line */
	#[McpTool('read_log_entries', 'Reads log entries from the framework log file. Optionally filter by date (Y/m/d) and limit results.')]
	public function readLogEntriesTool(string $date = '', int $limit = 100): array
	{
		if (empty($date)) {
			$date = date('Y/m/d');
		}

		$logPath = Path::getLogsDir() . '/' . $date . '.log';
		$file = File::open(Disk::Local, $logPath);

		if (!$file->exists()) {
			return [
				'entries' => [],
				'note' => "No log file found for date: $date",
			];
		}

		/** @var string[] $lines */
		$lines = array_filter((array) $file->readLines());
		$entries = array_slice(array_reverse($lines), 0, $limit);

		return [
			'date' => $date,
			'count' => count($entries),
			'entries' => $entries,
		];
	}

	/** @phpstan-ignore-next-line */
	#[McpTool('search_docs', 'Searches the generated wiki documentation for a given keyword and returns matching lines.')]
	public function searchDocsTool(string $keyword, int $limit = 30): array
	{
		$wikiFile = Path::getRootDir() . '/.github/wiki/Home.md';
		$file = File::open(Disk::Local, $wikiFile);

		if (!$file->exists()) {
			return [
				'matches' => [],
				'note' => 'Wiki docs not generated yet. Run: php assist doc:gen',
			];
		}

		/** @var string[] $lines */
		$lines = (array) $file->readLines();
		$matches = [];

		foreach ($lines as $lineNo => $line) {
			if (false !== stripos($line, $keyword)) {
				$matches[] = ['line' => $lineNo + 1, 'content' => trim($line)];
			}

			if (count($matches) >= $limit) {
				break;
			}
		}

		return [
			'keyword' => $keyword,
			'count' => count($matches),
			'matches' => $matches,
		];
	}

	//	#[McpTool('rhel_integration', 'Returns Interactive Shell on the Projject')]
	//	#[McpTool('route_inspection', 'Scans the project for route definition files and returns their contents.')]

	/** @phpstan-ignore-next-line */
	#[McpTool('assist_commands', 'Lists all registered console commands in the Sakoo Assist application.')]
	public function assistCommandsTool(): array
	{
		/** @var Application $application */
		$application = require Path::getAppDir() . '/Assist/Bootstrap.php';
		$commands = $application->getCommands();
		$list = array_map(fn (Command $cmd) => ['name' => $cmd::getName(), 'description' => $cmd::getDescription()], $commands);

		return ['commands' => array_values($list)];
	}
}
