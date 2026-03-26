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
 * Registers all MCP elements (Tools, Resources, Prompts) for the Sakoo Assist agent.
 *
 * Element types:
 *   - Tool     — LLM selects and invokes with user permission.
 *   - Resource — User attaches explicitly to their command context.
 *   - Prompt   — User runs as a shortcut/template.
 */
class McpElements
{
	//	#[McpTool('rhel_integration', 'Returns Interactive Shell on the Project')]
	//	#[McpTool('route_inspection', 'Scans the project for route definition files and returns their contents.')]
	//	#[McpTool('database_connections', 'Lists all configured database connections read from environment variables (DB_*).')]
	//	#[McpTool('database_query', 'Executes a read-only SQL query against the default PDO connection and returns results.')]
	//	#[McpTool('database_schema', 'Returns the schema (tables and columns) of the configured database.')]

	/**
	 * Reads the full content of a file at the given absolute path.
	 *
	 * @throws \InvalidArgumentException when $path is not a readable file
	 */
	#[McpTool('read_file', 'Reads content of a file in the Sakoo PHP Framework')]
	public function readFileTool(string $path): string
	{
		Assert::lazy()
			->file($path, "The path '{$path}' is not a file.")
			->notDir($path, "The path '{$path}' is a directory.")
			->validate();

		/** @var string[] $lines */
		$lines = File::open(Disk::Local, $path)->readLines();
		Assert::array($lines, "The path '{$path}' does not exist.");

		return implode(PHP_EOL, $lines);
	}

	/**
	 * Writes $content to a file at the given absolute path, creating it if absent.
	 */
	#[McpTool('write_file', 'Writes content in a new file in the Sakoo PHP Framework')]
	public function writeFileTool(string $path, string $content): string
	{
		$stored = File::open(Disk::Local, $path)->write($content);

		return $stored ? "File stored successfully at: $path" : "Failed to store file at: $path";
	}

	/**
	 * Returns a flat list of all files in the project root.
	 *
	 * @return array<string,string[]>
	 */
	#[McpTool('list_files', 'Extracts all of project files.')]
	public function getFilesListTool(): array
	{
		return $this->getFilesListResource();
	}

	/**
	 * Returns a flat list of all files inside $path, honouring VCS ignore rules.
	 *
	 * @return array<string,string[]>
	 */
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
	 * Builds a two-message prompt (system + user) from a stored Markdown prompt file.
	 *
	 * @return PromptMessage[]
	 */
	#[McpPrompt('feature_from_story')]
	public function featureFromStory(string $fileName): array
	{
		/** @var string[] $systemPrompt */
		$systemPrompt = File::open(Disk::Local, Path::getAppDir() . '/Assist/AI/Prompt/00-system-prompt.md')->readLines();

		/** @var string[] $userPrompt */
		$userPrompt = File::open(Disk::Local, Path::getAppDir() . "/Assist/AI/Prompt/{$fileName}")->readLines();

		return [
			new PromptMessage(Role::Assistant, new TextContent($systemPrompt)),
			new PromptMessage(Role::User, new TextContent($userPrompt)),
		];
	}

	/**
	 * Returns runtime metadata: mode, environment, replica ID, and key directory paths.
	 *
	 * @return array{
	 *     mode: 'Console'|'Http'|'Test',
	 *     environment: 'Debug'|'Production',
	 *     replica_id: string,
	 *     paths: array{
	 *          root: false|string,
	 *          storage: string,
	 *          logs: string,
	 *          vendor: string,
	 *          app: false|string,
	 *          system: false|string
	 *      }
	 *   }
	 */
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

	/**
	 * Returns the most recent HTTP VarDump entries written by the framework's HttpDumper.
	 *
	 * @return array{entries: string[], note?: string}
	 */
	#[McpTool('browser_logs', "Returns the latest HTTP VarDump entries stored by the framework's HttpDumper.")]
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

	/**
	 * Reads log entries for a given date (defaults to today). Entries are returned
	 * in reverse-chronological order so the most recent are first.
	 *
	 * @return array{date: string, count: int, entries: string[]}|array{entries: array<empty>, note: string}
	 */
	#[McpTool('read_log_entries', 'Reads log entries from the framework log file. Optionally filter by date (Y/m/d) and limit results.')]
	public function readLogEntriesTool(string $date = '', int $limit = 100): array
	{
		if ('' === $date) {
			$date = date('Y/m/d');
		}

		$logPath = Path::getLogsDir() . '/' . $date . '.log';
		$file = File::open(Disk::Local, $logPath);

		if (!$file->exists()) {
			return [
				'entries' => [],
				'note' => "No log file found for date: {$date}",
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

	/**
	 * Returns the last PHP error recorded via error_get_last(), if any.
	 *
	 * @return array{error: null, note: string}|array{type: int, message: string, file: string, line: int}
	 */
	#[McpTool('last_error', 'Returns the last PHP error recorded via error_get_last().')]
	public function lastErrorTool(): array
	{
		$error = error_get_last();

		if (null === $error) {
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

	/**
	 * Searches the generated wiki (Home.md) for lines matching $keyword (case-insensitive).
	 * Returns up to $limit matches with their 1-based line numbers.
	 *
	 * @return array{keyword: string, count: int, matches: list<array{line: int, content: string}>}|array{matches: array<empty>, note: string}
	 */
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

	/**
	 * Resolves an absolute URL for $path by prepending the APP_URL env variable.
	 */
	#[McpTool('get_absolute_url', 'Resolves an absolute URL for a given path using APP_URL from the environment.')]
	public function getAbsoluteUrlTool(string $path = '/'): string
	{
		/** @var string $appUrl */
		$appUrl = Env::get('APP_URL', 'http://localhost');
		$base = rtrim($appUrl, '/');
		$path = '/' . ltrim($path, '/');

		return $base . $path;
	}

	/**
	 * Lists all registered Sakoo Assist console commands with their names and descriptions.
	 *
	 * @return array{commands: list<array{name: string, description: string}>}
	 */
	#[McpTool('assist_commands', 'Lists all registered console commands in the Sakoo Assist application.')]
	public function assistCommandsTool(): array
	{
		/** @var Application $application */
		$application = require Path::getAppDir() . '/Assist/Bootstrap.php';
		$commands = $application->getCommands();

		/** @var array<array{name: string, description: string}> $list */
		$list = array_map(
			fn (Command $cmd): array => [
				'name' => $cmd::getName(),
				'description' => $cmd::getDescription(),
			],
			$commands,
		);

		return ['commands' => array_values($list)];
	}

	/**
	 * Estimates token usage across an entire prompt conversation.
	 * Real Scenario: Tools List + System Prompt + User Prompt + Tools Usage Request/Response.
	 *
	 * @return array{characters:int, tokens:int, model:string}
	 */
	#[McpTool('count_prompt_tokens', 'Estimates total token usage for a full prompt conversation')]
	public function countPromptTokensTool(string $message): array
	{
		$tokens = (new McpTokenCalculator())->countText($message);

		return [
			'characters' => mb_strlen($message),
			'tokens' => $tokens,
			'model' => 'cl100k_base',
		];
	}
}
