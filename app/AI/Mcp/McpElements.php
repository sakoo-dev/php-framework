<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ExceptionInterface;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Mcp\Schema\Result\CallToolResult;
use Sakoo\AI\Mcp\McpElements as BaseElements;

/**
 * Sakoo PHP Framework-specific MCP capabilities.
 *
 * Extends the generic {@see BaseElements} with tools, resources, and prompts
 * that are tightly coupled to the Sakoo framework's directory structure,
 * CLI commands, evaluation system, and skill file library.
 *
 * Generic capabilities (file I/O, git, shell, web, code analysis) live in
 * BaseElements so they can be reused in other host applications.
 */
class McpElements extends BaseElements
{
	private const CHECK_OUTPUT_CAP = 10000;

	#[McpTool(
		name: 'project_structure',
		description: 'Return a compact file tree of app/, core/, and system/ grouped by section. Vendor, dot-files, and VCS-ignored paths are excluded. Use this for orientation before deciding which files to read.',
	)]
	public function projectStructureTool(): CallToolResult
	{
		$appFiles = $this->context->listFiles($this->context->appDir() ?: __DIR__);
		$systemFiles = $this->context->listFiles($this->context->systemDir() ?: __DIR__);
		$coreFiles = $this->context->listFiles($this->context->coreDir() ?: __DIR__);

		$structured = ['app' => $appFiles, 'core' => $coreFiles, 'system' => $systemFiles];

		$result = new CallToolResult(
			[new TextContent($structured)],
			structuredContent: $structured,
		);

		$this->observer->log('project_structure', [], $structured);

		return $result;
	}

	#[McpTool(
		name: 'browser_logs',
		description: 'Return the latest HTTP VarDump entries from storage/browser/http.log, newest first. Useful for inspecting live request/response data captured via vd() calls. Returns a notice when the log file is absent.',
	)]
	public function browserLogsTool(int $limit = 50): CallToolResult
	{
		$logPath = $this->context->storageDir() . '/browser/http.log';

		if (!$this->fileStorage->exists($logPath)) {
			return CallToolResult::success([new TextContent('No browser log found.')], ['note' => 'no_log']);
		}

		$allLines = array_values(array_filter(explode("\n", $this->fileStorage->read($logPath)), fn (string $l): bool => '' !== trim($l)));
		$entries = array_slice($allLines, -$limit);

		$result = new CallToolResult(
			[new TextContent($entries)],
			structuredContent: ['entries' => $entries],
		);

		$this->observer->log('browser_logs', ['limit' => $limit], $entries);

		return $result;
	}

	#[McpTool(
		name: 'read_log_entries',
		description: 'Read Sakoo framework log entries for a given date (Y/m/d format). Defaults to today. Entries are returned tail-first so the most recent activity appears first. Returns a notice when the date file does not exist.',
	)]
	public function readLogEntriesTool(string $date = '', int $limit = 100): CallToolResult
	{
		if ('' === $date) {
			$date = date('Y/m/d');
		}

		$logPath = $this->context->logsDir() . '/' . $date . '.log';

		if (!$this->fileStorage->exists($logPath)) {
			return CallToolResult::success(
				[new TextContent("No log file for {$date}")],
				['date' => $date, 'n' => 0, 'note' => 'no_log'],
			);
		}

		$allLines = array_values(array_filter(explode("\n", $this->fileStorage->read($logPath)), fn (string $l): bool => '' !== trim($l)));
		$entries = array_slice($allLines, -$limit);

		$result = new CallToolResult(
			[new TextContent($entries)],
			structuredContent: ['entries' => $entries],
			meta: ['date' => $date, 'n' => count($entries)],
		);

		$this->observer->log('read_log_entries', compact('date', 'limit'), $entries);

		return $result;
	}

	#[McpTool(
		name: 'search_docs',
		description: 'Search the generated wiki (.github/wiki/Home.md) for lines matching a keyword using a case-insensitive substring match. Returns matched lines with their line numbers. Run "php assist doc:gen" first if the wiki has not been generated yet.',
	)]
	public function searchDocsTool(string $keyword, int $limit = 30): CallToolResult
	{
		$wikiFile = $this->context->rootDir() . '/.github/wiki/Home.md';

		if (!$this->fileStorage->exists($wikiFile)) {
			return CallToolResult::success(
				[new TextContent('Wiki not generated. Run: php assist doc:gen')],
				['kw' => $keyword, 'n' => 0],
			);
		}

		$lines = explode("\n", $this->fileStorage->read($wikiFile));
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

	#[McpTool(
		name: 'check_code',
		description: 'Run PHPStan, PHPUnit, PHP-CS-Fixer, and AI Evals together as a full quality gate. Each tool is reported individually in structuredContent. Set fix=true to let PHP-CS-Fixer auto-correct style violations before evaluating the lint result.',
	)]
	public function checkCodeTool(bool $fix = false): CallToolResult
	{
		$phpstan = $this->compactCheckResult($this->shell->phpstanParsed());
		$phpunit = $this->compactCheckResult($this->shell->phpunitParsed());
		$lint = $this->compactCheckResult($this->shell->phpCsFixerParsed($fix));
		$evals = $this->shell->evaluationsParsed();

		$allOk = $phpstan['ok'] && $phpunit['ok'] && $lint['ok'] && $evals['ok'];
		$summaryText = sprintf(
			'%s | phpstan:%s phpunit:%s lint:%s evals:%s',
			$allOk ? 'All checks passed' : 'Checks failed',
			$phpstan['ok'] ? 'ok' : 'fail',
			$phpunit['ok'] ? 'ok' : 'fail',
			$lint['ok'] ? 'ok' : 'fail',
			$evals['ok'] ? sprintf('ok(%d/%d)', $evals['passed'], $evals['total']) : sprintf('fail(%d/%d)', $evals['failed'], $evals['total']),
		);

		$structured = [
			'ok' => $allOk,
			'phpstan' => $phpstan,
			'phpunit' => $phpunit,
			'lint' => $lint,
			'evals' => [
				'ok' => $evals['ok'],
				'passed' => $evals['passed'],
				'failed' => $evals['failed'],
				'total' => $evals['total'],
				'output' => $evals['output'],
			],
		];

		$result = new CallToolResult(
			[new TextContent($summaryText)],
			isError: !$allOk,
			structuredContent: $structured,
		);

		$this->observer->log('check_code', ['fix' => $fix, 'evalsOk' => $evals['ok']], $summaryText);

		return $result;
	}

	#[McpTool(
		name: 'run_evals',
		description: 'Run the full AI evaluation suite (@./sakoo composer eval) and return a pass/fail summary with counts. Evals cover all Guard detectors: PII masking, prompt injection, unethical content, and illegal content. isError is set when any eval fails so the LLM is prompted to investigate.',
	)]
	public function runEvalsTool(): CallToolResult
	{
		$parsed = $this->shell->evaluationsParsed();

		$summaryText = sprintf(
			'%s | passed:%d failed:%d total:%d',
			$parsed['ok'] ? 'Evals passed' : 'Evals failed',
			$parsed['passed'],
			$parsed['failed'],
			$parsed['total'],
		);

		$result = new CallToolResult(
			[new TextContent($summaryText . "\n\n" . $parsed['output'])],
			isError: !$parsed['ok'],
			structuredContent: [
				'ok' => $parsed['ok'],
				'passed' => $parsed['passed'],
				'failed' => $parsed['failed'],
				'total' => $parsed['total'],
				'exitCode' => $parsed['exitCode'],
			],
		);

		$this->observer->log('run_evals', [], $summaryText);

		return $result;
	}

	#[McpTool(
		name: 'sakoo_exec',
		description: 'Execute a ./sakoo sub-command and return its output with exit code. Allowed prefixes: assist, composer, npm — all others are rejected by the shell guard. The result is marked isError on non-zero exit so the LLM can react without an extra tool call.',
	)]
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

	#[McpResource(
		uri: 'prompt://system',
		name: 'Senior-Engineer-System-Prompt',
		description: 'Core identity block for the senior PHP engineer persona: behavioral rules, PHP/PSR standards, and architectural principles. Loaded into the system prompt of all developer-facing agents.',
		mimeType: 'text/markdown',
	)]
	public function systemPromptResource(): string
	{
		return $this->readSkillFile('Skill/software-engineer.md');
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource(
		uri: 'project://structure',
		name: 'Project-Structure',
		description: 'Compact file tree of app/, core/, and system/ grouped by section. Vendor and ignored paths are excluded. Significantly fewer tokens than file://list — prefer this for orientation.',
	)]
	public function projectStructureResource(): array
	{
		$result = $this->projectStructureTool();

		return $result->structuredContent ?? [];
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource(
		uri: 'project://info',
		name: 'Application-Runtime-Info',
		description: 'Runtime metadata for the current kernel instance: mode, environment, replica ID, and all resolved directory paths (root, storage, logs, vendor, app, system). Use to orient the agent to where the application is running.',
	)]
	public function applicationInfoResource(): array
	{
		return $this->context->runtimeInfo();
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource(
		uri: 'project://makefile',
		name: 'Makefile-Targets',
		description: 'Structured list of all Makefile targets with names and descriptions. Provides a quick inventory of available developer commands (start, check, test, doc, benchmark, etc.) without requiring the agent to parse the Makefile itself.',
	)]
	public function makefileTargetsResource(): array
	{
		return ['targets' => $this->context->makefileTargets()];
	}

	/**
	 * @phpstan-ignore missingType.iterableValue
	 */
	#[McpResource(
		uri: 'project://commands',
		name: 'Assist-CLI-Commands',
		description: 'All registered "php assist" commands with their names and descriptions. Bootstrapped from the Assist console application. Use to discover available CLI operations before invoking them via the sakoo_exec tool.',
	)]
	public function assistCommandsResource(): array
	{
		return ['commands' => $this->context->registeredCommands()];
	}

	#[McpResource(
		uri: 'skill://architecture',
		name: 'Architecture-Skill',
		description: 'Authoritative Sakoo architecture guide: SOLID principles in practice, DDD layer boundaries, approved patterns (Value Objects, Aggregates, Service Loaders), and hard design rules. Load before proposing or reviewing any architectural decision.',
		mimeType: 'text/markdown',
	)]
	public function architectureSkillResource(): string
	{
		return $this->readSkillFile('Skill/architecture.md');
	}

	#[McpResource(
		uri: 'skill://conventions',
		name: 'Coding-Conventions',
		description: 'Sakoo style guide: strict-types declaration, PSR-4 namespacing, use-block ordering, full type annotation rules, naming conventions, and PHP-CS-Fixer formatting standards. Load before writing or reviewing any PHP code.',
		mimeType: 'text/markdown',
	)]
	public function conventionsResource(): string
	{
		return $this->readSkillFile('Skill/coding-conventions.md');
	}

	#[McpResource(
		uri: 'skill://sakoo-identity',
		name: 'Sakoo-Identity',
		description: 'Framework identity and positioning: what Sakoo is, its six value propositions, competitive stance against Laravel/Symfony, and the principles that define it. Use when generating documentation, READMEs, or marketing content.',
		mimeType: 'text/markdown',
	)]
	public function sakooIdentityResource(): string
	{
		return $this->readSkillFile('Skill/sakoo-identity.md');
	}

	#[McpResource(
		uri: 'skill://prompt-engineering',
		name: 'Prompt-Engineering-Skill',
		description: '3-tier prompt architecture (system/task/context), token budget rules per tier, guidelines for writing MCP attribute descriptions, and common prompt anti-patterns to avoid. Load when writing or reviewing system prompts or MCP definitions.',
		mimeType: 'text/markdown',
	)]
	public function promptEngineeringResource(): string
	{
		return $this->readSkillFile('Skill/prompt-engineering.md');
	}

	#[McpResource(
		uri: 'skill://quality-assurance',
		name: 'Quality-Assurance-Checklist',
		description: 'Comprehensive code-review checklist: layer placement, dependency rules, aggregate boundaries, type safety, test coverage expectations, and the definition of done for a Sakoo pull request. Load before submitting or reviewing any change.',
		mimeType: 'text/markdown',
	)]
	public function qualityAssuranceResource(): string
	{
		return $this->readSkillFile('Skill/quality-assurance.md');
	}

	#[McpResource(
		uri: 'skill://file-handling',
		name: 'File-Handling-Skill',
		description: 'Decision rules for reading files by size (full read / section sampling / grep), batch navigation patterns using MCP tools, and guidance on avoiding token waste with large files. Load before reading any file larger than 20 KB.',
		mimeType: 'text/markdown',
	)]
	public function fileHandlingResource(): string
	{
		return $this->readSkillFile('Skill/file-handling.md');
	}

	#[McpResource(
		uri: 'skill://security-checklist',
		name: 'Security-Checklist',
		description: 'Security review checklist covering authentication, authorization, session management, input/output validation, cryptography, availability, error handling, secure design, logging, server configuration, and VPS hardening. Load before reviewing or designing any security-sensitive feature.',
		mimeType: 'text/markdown',
	)]
	public function securityChecklistResource(): string
	{
		return $this->readSkillFile('Skill/security-checklist.md');
	}

	/**
	 * @return PromptMessage[]
	 */
	#[McpPrompt(
		name: 'dev_task',
		description: 'Load a story file as a structured developer task. Combines the senior engineer system prompt with a task file from /Prompt/ (e.g. Story/01-http-request-module.md) into a two-message prompt ready for agent invocation.',
	)]
	public function devTaskPrompt(string $fileName): array
	{
		$userPromptPath = $this->resolvePromptFilePath($fileName);
		$systemPrompt = $this->fileStorage->read($this->context->promptDir() . '/Role/software-engineer.md');
		$userPrompt = $this->fileStorage->read($userPromptPath);

		return [
			new PromptMessage(Role::Assistant, new TextContent("[System context]\n" . $systemPrompt)),
			new PromptMessage(Role::User, new TextContent($userPrompt)),
		];
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	private function resolvePromptFilePath(string $fileName): string
	{
		$promptBase = $this->context->guardPath($this->context->promptDir());
		$resolved = $this->context->guardPath($promptBase . '/' . ltrim($fileName, '/'));
		$prefix = rtrim($promptBase, '/') . '/';

		if (!str_starts_with($resolved, $prefix)) {
			throw new \InvalidArgumentException('Prompt path escapes ' . $this->context->promptDir() . ": {$fileName}");
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

	private function readSkillFile(string $relativePath): string
	{
		return $this->fileStorage->read($this->context->promptDir() . "/$relativePath");
	}
}
