<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use Mcp\Exception\InvalidArgumentException;
use Mcp\Exception\RuntimeException;
use Sakoo\Framework\Core\Finder\FileFinder;
use System\Path\Path;

/**
 * Project-scoped shell command executor with a closed command vocabulary.
 *
 * Every executable command is exposed as a named method with typed parameters.
 * The run() method is public to support the shell_exec MCP tool, but callers
 * should prefer specific methods (git, sakoo, phpunit, etc.) when available.
 *
 * Security constraints:
 *   - Working directory is always the project root (never configurable).
 *   - Structured git helpers escape every argument via {@see escapeshellarg()}.
 *   - Public git() input is constrained to a safe character allowlist.
 *   - STDERR is merged into STDOUT so errors are never silently lost.
 *   - Git commands are scoped via `git -C {root}` automatically.
 *   - The `sakoo()` method enforces a sub-command allowlist.
 *   - The `run()` method accepts arbitrary commands but logs all usage.
 *
 * Uses {@see proc_open()} internally for reliable stdout+stderr capture.
 *
 * @see McpElements              The sole consumer of this class.
 * @see InvalidArgumentException Thrown on disallowed sub-commands.
 * @see RuntimeException         Thrown on process failures when $throw is true.
 */
final class McpShell
{
	/** Maximum bytes to read from a child process stdout/stderr to prevent OOM. */
	private const MAX_OUTPUT_BYTES = 1_048_576;

	/** Sub-commands allowed by {@see sakoo()}. */
	private const SAKOO_ALLOWED = ['assist', 'composer', 'npm'];

	/** Read-only git sub-commands allowed by {@see git()}. */
	private const GIT_ALLOWED = ['log', 'diff', 'status', 'rev-parse', 'show'];

	/** Splits command strings on spaces/tabs while keeping newlines detectable. */
	private const ARG_SPLIT_PATTERN = '/[ \t]+/';

	/** Blocks command separators written as control characters (LF, CR, NUL). */
	private const CONTROL_CHARS_PATTERN = '/[\r\n\0]/';

	/** Restricts git() input to a conservative safe character set. */
	private const GIT_ALLOWED_CHARS_PATTERN = '/[^a-zA-Z0-9_\-.:=\/ \t]/';

	/** Safe character pattern for git refs (branches, tags, remotes). */
	private const SAFE_REF_PATTERN = '/^[a-zA-Z0-9_\-\.\/]+$/';

	/** Maximum lines to read from a file tail operation. */
	private const MAX_TAIL_LINES = 1000;

	/** Default number of lines for tail operations. */
	private const DEFAULT_TAIL_LINES = 50;

	/** Maximum number of processes to list. */
	private const MAX_PROCESS_LIST = 200;

	/** Maximum ping count for network tests. */
	private const MAX_PING_COUNT = 10;

	/**
	 * Runs a git sub-command scoped to the project root.
	 *
	 * Only read-only git operations are permitted. The sub-command name is
	 * validated against a fixed allowlist before execution. The command string
	 * must also pass a strict character allowlist to block shell metacharacters.
	 *
	 * @param string $subCommand git sub-command with arguments (e.g. "log -n 10")
	 *
	 * @return array{output: string, exitCode: int}
	 *
	 * @throws InvalidArgumentException when the git sub-command is not in the allowlist
	 */
	public function git(string $subCommand): array
	{
		$subCommand = trim($subCommand);
		$parts = $this->splitCommandArgs($subCommand);
		$verb = array_shift($parts) ?? '';

		if (!in_array($verb, self::GIT_ALLOWED, true)) {
			throw new InvalidArgumentException("Git sub-command not allowed: {$verb}. Allowed: " . implode(', ', self::GIT_ALLOWED));
		}

		throwIf(
			1 === preg_match(self::CONTROL_CHARS_PATTERN, $subCommand),
			new InvalidArgumentException('Git command contains unsupported control characters.')
		);

		throwIf(
			1 === preg_match(self::GIT_ALLOWED_CHARS_PATTERN, $subCommand),
			new InvalidArgumentException('Git command contains unsupported characters.')
		);

		return $this->gitCommand($verb, $parts);
	}

	/**
	 * Returns parsed git log commits.
	 *
	 * @param int $limit maximum commits (hard max: 200)
	 * @param string $path optional file path filter (already guarded by caller)
	 *
	 * @return array{commits: array<array{hash: string, msg: string}>}
	 */
	public function gitLogParsed(int $limit = 20, string $path = ''): array
	{
		$limit = min($limit, 200);
		$args = ['--format=%h|%s', '--no-decorate', '-n', (string) $limit];

		if ('' !== $path) {
			$args[] = '--';
			$args[] = $path;
		}

		['output' => $output] = $this->gitCommand('log', $args);

		$lines = array_filter(explode("\n", trim($output)));

		$commits = array_map(static function (string $line): array {
			$parts = explode('|', $line, 2);

			return ['hash' => $parts[0], 'msg' => $parts[1] ?? ''];
		}, $lines);

		return ['commits' => $commits];
	}

	/**
	 * Returns parsed git diff with truncation control.
	 *
	 * @param string $ref git ref to diff against (empty = unstaged)
	 * @param string $path optional file path filter (already guarded by caller)
	 * @param int $maxLines maximum lines (hard max: 1000)
	 *
	 * @return array{diff: string, total: int, truncated: bool}
	 */
	public function gitDiffParsed(string $ref = '', string $path = '', int $maxLines = 200): array
	{
		$maxLines = min($maxLines, 1000);
		$args = [];

		if ('' !== $ref) {
			$args[] = $ref;
		}

		if ('' !== $path) {
			$args[] = '--';
			$args[] = $path;
		}

		['output' => $raw] = $this->gitCommand('diff', $args);

		$lines = explode("\n", trim($raw));
		$total = count($lines);
		$truncated = $total > $maxLines;

		if ($truncated) {
			$lines = array_slice($lines, 0, $maxLines);
		}

		return ['diff' => implode("\n", $lines), 'total' => $total, 'truncated' => $truncated];
	}

	/**
	 * Returns parsed git status with structured file list and summary.
	 *
	 * @param string $path optional file path filter (already guarded by caller)
	 *
	 * @return array{clean: bool, files: array<array{status: string, file: string}>, summary: string}
	 */
	public function gitStatusParsed(string $path = ''): array
	{
		$args = ['--porcelain'];

		if ('' !== $path) {
			$args[] = '--';
			$args[] = $path;
		}

		['output' => $raw] = $this->gitCommand('status', $args);

		return self::parseGitStatusPorcelain($raw);
	}

	/**
	 * Parses git status --porcelain output into structured files and summary counts.
	 *
	 * @return array{clean: bool, files: array<array{status: string, file: string}>, summary: string}
	 */
	public static function parseGitStatusPorcelain(string $raw): array
	{
		$raw = rtrim($raw);

		if ('' === trim($raw)) {
			return ['clean' => true, 'files' => [], 'summary' => 'Working tree clean'];
		}

		$lines = preg_split('/\R/', $raw) ?: [];
		$lines = array_values(array_filter($lines, static fn (string $line): bool => '' !== $line));
		$files = [];
		$staged = 0;
		$modified = 0;
		$untracked = 0;

		foreach ($lines as $line) {
			$xy = substr($line, 0, 2);
			$index = $xy[0] ?? ' ';
			$worktree = $xy[1] ?? ' ';
			$file = trim(substr($line, 3));
			$status = trim($xy);

			if ('??' === $xy) {
				++$untracked;
			} else {
				if (' ' !== $index) {
					++$staged;
				}

				if (' ' !== $worktree) {
					++$modified;
				}
			}

			$files[] = ['status' => '' !== $status ? $status : '  ', 'file' => $file];
		}

		return [
			'clean' => false,
			'files' => $files,
			'summary' => "staged:{$staged} modified:{$modified} untracked:{$untracked}",
		];
	}

	/**
	 * Runs PHPUnit with an optional test filter.
	 *
	 * Excludes the "integration" group by default to prevent recursive process
	 * spawning when check_code runs the full suite.
	 *
	 * @param string $filter PHPUnit --filter value; empty = run all
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function phpunit(string $filter = ''): array
	{
		$filterArg = '' !== $filter ? ' --filter=' . escapeshellarg($filter) : '';

		return $this->run("php vendor/bin/phpunit{$filterArg} --exclude-group=integration --no-progress --colors=never");
	}

	/**
	 * Runs PHPUnit and parses the result into a structured summary.
	 *
	 * @param string $filter PHPUnit --filter value; empty = run all
	 *
	 * @return array{ok: bool, summary: string, output: string, exitCode: int}
	 */
	public function phpunitParsed(string $filter = ''): array
	{
		$result = $this->phpunit($filter);
		$output = $result['output'];
		$summary = self::parsePhpunitOutput($output);

		return [
			'ok' => 0 === $result['exitCode'] && $summary['ok'],
			'summary' => $summary['summary'],
			'output' => $output,
			'exitCode' => $result['exitCode'],
		];
	}

	/**
	 * Runs the NeuronAI evaluations suite via the composer eval script.
	 *
	 * Executes `composer eval` (mapped to vendor/bin/neuron evaluation --path=app/AI/Evals)
	 * and returns raw output, exit code, and parsed pass/fail counts.
	 *
	 * @return array{ok: bool, output: string, exitCode: int, passed: int, failed: int, total: int}
	 */
	public function evaluationsParsed(): array
	{
		$result = $this->run('composer eval --no-interaction 2>&1');
		$output = $result['output'];
		$exitCode = $result['exitCode'];

		preg_match('/(\d+)\s+passed/i', $output, $passedMatch);
		preg_match('/(\d+)\s+failed/i', $output, $failedMatch);

		$passed = (int) ($passedMatch[1] ?? 0);
		$failed = (int) ($failedMatch[1] ?? 0);

		return [
			'ok' => 0 === $exitCode,
			'output' => $output,
			'exitCode' => $exitCode,
			'passed' => $passed,
			'failed' => $failed,
			'total' => $passed + $failed,
		];
	}

	/**
	 * Runs PHPStan static analysis.
	 *
	 * Uses a conservative memory limit to avoid OOM in constrained containers.
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function phpstan(): array
	{
		return $this->run('php vendor/bin/phpstan analyse -c phpstan.neon --memory-limit=256M --no-progress --error-format=raw');
	}

	/**
	 * Runs PHPStan and parses the result into a structured summary.
	 *
	 * @return array{ok: bool, output: string, exitCode: int}
	 */
	public function phpstanParsed(): array
	{
		$result = $this->phpstan();

		return [
			'ok' => 0 === $result['exitCode'] && self::parsePhpstanOutput($result['output']),
			'output' => $result['output'],
			'exitCode' => $result['exitCode'],
		];
	}

	/**
	 * Runs PHP-CS-Fixer in check or fix mode.
	 *
	 * @param bool $fix when true, auto-fixes files instead of just reporting
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function phpCsFixer(bool $fix = false): array
	{
		$mode = $fix ? 'fix' : 'check';
		$root = (string) Path::getRootDir();
		$configPath = $root . '/.php-cs-fixer.php';

		if (is_file($configPath)) {
			return $this->run("php vendor/bin/php-cs-fixer {$mode} --config=" . escapeshellarg($configPath) . ' --using-cache=no');
		}

		return $this->run("php vendor/bin/php-cs-fixer {$mode} app/ core/src/ system/ --using-cache=no");
	}

	/**
	 * Runs PHP-CS-Fixer and parses the result into a structured summary.
	 *
	 * @param bool $fix when true, auto-fixes instead of just checking
	 *
	 * @return array{ok: bool, output: string, exitCode: int}
	 */
	public function phpCsFixerParsed(bool $fix = false): array
	{
		$result = $this->phpCsFixer($fix);

		return [
			'ok' => 0 === $result['exitCode'] && self::parseCsFixerOutput($result['output']),
			'output' => $result['output'],
			'exitCode' => $result['exitCode'],
		];
	}

	/**
	 * Runs PHPUnit tests with a specific filter and returns parsed results.
	 *
	 * @return array{filter: string, output: string, exitCode: int, passed: bool}
	 */
	public function runTestsFiltered(string $filter): array
	{
		$result = $this->phpunit($filter);
		$parsed = self::parsePhpunitOutput($result['output']);

		return [
			'filter' => $filter,
			'output' => $result['output'],
			'exitCode' => $result['exitCode'],
			'passed' => 0 === $result['exitCode'] && $parsed['ok'],
		];
	}

	/**
	 * Executes performance benchmarks via composer.
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function benchmarkRun(): array
	{
		return $this->sakoo('composer benchmark');
	}

	/**
	 * Auto-fixes code style issues with PHP-CS-Fixer.
	 *
	 * @return array{output: string, exitCode: int, fixed: bool}
	 */
	public function lintFix(string $path = ''): array
	{
		$result = $this->phpCsFixer(fix: true);

		return [
			'output' => $result['output'],
			'exitCode' => $result['exitCode'],
			'fixed' => 0 === $result['exitCode'],
		];
	}

	/**
	 * Formats code in a specific file or directory.
	 *
	 * @return array{path: string, output: string, exitCode: int, formatted: bool}
	 */
	public function formatCode(string $path): array
	{
		$command = 'php vendor/bin/php-cs-fixer fix ' . escapeshellarg($path) . ' --using-cache=no';
		$result = $this->run($command);

		return [
			'path' => $path,
			'output' => $result['output'],
			'exitCode' => $result['exitCode'],
			'formatted' => 0 === $result['exitCode'],
		];
	}

	/**
	 * Runs PHPUnit with Clover XML coverage output to a temporary file.
	 *
	 * @param string $filter PHPUnit --filter value; empty = run all
	 *
	 * @return array{output: string, exitCode: int, cloverPath: string}
	 */
	public function phpunitCoverage(string $filter = ''): array
	{
		$cloverPath = sys_get_temp_dir() . '/phpunit_coverage.xml';
		$filterArg = '' !== $filter ? ' --filter=' . escapeshellarg($filter) : '';

		$result = $this->run(
			'php vendor/bin/phpunit' . $filterArg
			. ' --exclude-group=integration --no-progress --colors=never'
			. ' --coverage-clover=' . escapeshellarg($cloverPath)
		);

		return $result + ['cloverPath' => $cloverPath];
	}

	/**
	 * Runs PHPUnit with coverage and returns parsed statistics and uncovered lines.
	 *
	 * @param string $filter PHPUnit --filter value; empty = run all
	 *
	 * @return array{ok: bool, summary: string, stats: array<string, mixed>, files: array<int, array<string, mixed>>, exitCode: int}
	 */
	public function phpunitCoverageParsed(string $filter = ''): array
	{
		$run = $this->phpunitCoverage($filter);
		$output = $run['output'];
		$cloverPath = $run['cloverPath'];

		$base = self::parsePhpunitOutput($output);
		$coverage = self::parseCloverXml($cloverPath);

		return [
			'ok' => 0 === $run['exitCode'] && $base['ok'],
			'summary' => $base['summary'],
			'stats' => $coverage['stats'],
			'files' => $coverage['files'],
			'exitCode' => $run['exitCode'],
		];
	}

	/**
	 * Runs a git command with argument-level shell escaping.
	 *
	 * @param string $verb git sub-command verb
	 * @param string[] $args git arguments
	 *
	 * @return array{output: string, exitCode: int}
	 */
	private function gitCommand(string $verb, array $args = []): array
	{
		if (!in_array($verb, self::GIT_ALLOWED, true)) {
			throw new InvalidArgumentException("Git sub-command not allowed: {$verb}. Allowed: " . implode(', ', self::GIT_ALLOWED));
		}

		$root = (string) Path::getRootDir();
		$command = 'git -C ' . escapeshellarg($root) . ' ' . $verb;

		foreach ($args as $arg) {
			$command .= ' ' . escapeshellarg($arg);
		}

		return $this->run($command);
	}

	/**
	 * Parses a PHPUnit Clover XML file into summary stats and per-file uncovered lines.
	 *
	 * Files are sorted by line coverage percentage ascending (worst coverage first).
	 *
	 * @return array{stats: array<string, mixed>, files: array<int, array<string, mixed>>}
	 */
	public static function parseCloverXml(string $path): array
	{
		if (!is_file($path)) {
			return ['stats' => [], 'files' => []];
		}

		$xml = simplexml_load_file($path);

		if (false === $xml) {
			return ['stats' => [], 'files' => []];
		}

		$root = rtrim((string) Path::getRootDir(), '/') . '/';

		$stats = [];
		$metrics = $xml->project->metrics ?? null;

		if (null !== $metrics) {
			$totalStmts = (int) $metrics['statements'];
			$coveredStmts = (int) $metrics['coveredstatements'];
			$totalMethods = (int) $metrics['methods'];
			$coveredMethods = (int) $metrics['coveredmethods'];
			$totalClasses = (int) $metrics['classes'];
			$coveredClasses = (int) $metrics['coveredclasses'];

			$stats = [
				'lines_pct' => $totalStmts > 0 ? round($coveredStmts / $totalStmts * 100, 2) : 0.0,
				'lines_covered' => $coveredStmts,
				'lines_total' => $totalStmts,
				'methods_pct' => $totalMethods > 0 ? round($coveredMethods / $totalMethods * 100, 2) : 0.0,
				'methods_covered' => $coveredMethods,
				'methods_total' => $totalMethods,
				'classes_pct' => $totalClasses > 0 ? round($coveredClasses / $totalClasses * 100, 2) : 0.0,
				'classes_covered' => $coveredClasses,
				'classes_total' => $totalClasses,
			];
		}

		$files = [];

		foreach ($xml->project->file ?? [] as $file) {
			$filePath = str_replace($root, '', (string) $file['name']);
			$uncoveredLines = [];

			foreach ($file->line ?? [] as $line) {
				if ('stmt' === (string) $line['type'] && 0 === (int) $line['count']) {
					$uncoveredLines[] = (int) $line['num'];
				}
			}

			$fileMetrics = $file->metrics ?? null;

			if (null === $fileMetrics) {
				continue;
			}

			$stmts = (int) $fileMetrics['statements'];
			$covered = (int) $fileMetrics['coveredstatements'];
			$pct = $stmts > 0 ? round($covered / $stmts * 100, 2) : 100.0;

			$files[] = [
				'file' => $filePath,
				'lines_pct' => $pct,
				'lines_covered' => $covered,
				'lines_total' => $stmts,
				'uncovered_lines' => $uncoveredLines,
			];
		}

		usort($files, static fn (array $a, array $b): int => $a['lines_pct'] <=> $b['lines_pct']);

		return ['stats' => $stats, 'files' => $files];
	}

	/**
	 * Parses raw PHPUnit output into ok/summary.
	 *
	 * @return array{ok: bool, summary: string}
	 */
	public static function parsePhpunitOutput(string $output): array
	{
		$summary = '';
		$ok = false;

		foreach (array_reverse(explode("\n", $output)) as $line) {
			if (preg_match('/^(OK|FAILURES|ERRORS|Tests:)/', trim($line))) {
				$summary = trim($line);
				$ok = str_starts_with($summary, 'OK');

				break;
			}
		}

		return ['ok' => $ok, 'summary' => $summary];
	}

	/**
	 * Parses raw PHPStan output to determine pass/fail.
	 */
	public static function parsePhpstanOutput(string $output): bool
	{
		return !str_contains($output, ' Error') && !str_contains($output, 'Fatal');
	}

	/**
	 * Parses raw PHP-CS-Fixer output to determine pass/fail.
	 */
	public static function parseCsFixerOutput(string $output): bool
	{
		return !str_contains($output, 'FAIL') && !str_contains($output, 'Files that need fixing');
	}

	/**
	 * Runs an allowlisted sakoo sub-command.
	 *
	 * Only `assist`, `composer`, and `npm` are permitted. The full command
	 * string is sanitised via {@see escapeshellcmd()} after validation.
	 *
	 * @param string $command full command (e.g. "composer info", "npm list")
	 *
	 * @return array{output: string, exitCode: int}
	 *
	 * @throws InvalidArgumentException when the sub-command is not in the allowlist
	 */
	public function sakoo(string $command): array
	{
		$command = trim($command);

		throwIf(
			'' === $command,
			new InvalidArgumentException('Sakoo command cannot be empty.')
		);

		throwIf(
			1 === preg_match(self::CONTROL_CHARS_PATTERN, $command),
			new InvalidArgumentException('Sakoo command contains unsupported control characters.')
		);

		$parts = $this->splitCommandArgs($command, 2);
		$sub = $parts[0] ?? '';

		if (!in_array($sub, self::SAKOO_ALLOWED, true)) {
			throw new InvalidArgumentException('Sakoo sub-command not allowed: ' . $sub . '. Allowed: ' . implode(', ', self::SAKOO_ALLOWED));
		}

		return $this->run(escapeshellcmd($command));
	}

	/**
	 * Splits a command string into whitespace-delimited arguments.
	 *
	 * Newlines are intentionally not part of the split pattern so they can be
	 * rejected separately as control characters.
	 *
	 * @return string[]
	 */
	private function splitCommandArgs(string $command, int $limit = -1): array
	{
		return preg_split(self::ARG_SPLIT_PATTERN, $command, $limit, PREG_SPLIT_NO_EMPTY) ?: [];
	}

	/**
	 * Creates a git commit with the given message.
	 *
	 * @return array{hash: string, message: string, output: string}
	 */
	public function gitCommit(string $message, bool $all = false): array
	{
		if ('' === trim($message)) {
			throw new InvalidArgumentException('Commit message cannot be empty.');
		}

		$flags = $all ? '-a' : '';
		$command = 'commit ' . $flags . ' -m ' . escapeshellarg($message);

		$result = $this->gitCommand($command, []);

		if (0 !== $result['exitCode']) {
			throw new RuntimeException("Git commit failed: {$result['output']}");
		}

		$hashResult = $this->gitCommand('rev-parse', ['HEAD']);
		$hash = trim($hashResult['output']);

		return [
			'hash' => substr($hash, 0, 8),
			'message' => $message,
			'output' => $result['output'],
		];
	}

	/**
	 * Pushes commits to remote repository.
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function gitPush(string $remote = 'origin', string $branch = '', bool $setUpstream = false): array
	{
		$this->validateRef($remote);

		if ('' !== $branch) {
			$this->validateRef($branch);
		}

		$args = [];

		if ($setUpstream) {
			$args[] = '-u';
		}

		$args[] = $remote;

		if ('' !== $branch) {
			$args[] = $branch;
		}

		$result = $this->gitCommand('push', $args);

		if (0 !== $result['exitCode']) {
			throw new RuntimeException("Git push failed: {$result['output']}");
		}

		return $result;
	}

	/**
	 * Pulls changes from remote repository.
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function gitPull(string $remote = 'origin', string $branch = ''): array
	{
		$this->validateRef($remote);

		if ('' !== $branch) {
			$this->validateRef($branch);
		}

		$args = [$remote];

		if ('' !== $branch) {
			$args[] = $branch;
		}

		$result = $this->gitCommand('pull', $args);

		if (0 !== $result['exitCode']) {
			throw new RuntimeException("Git pull failed: {$result['output']}");
		}

		return $result;
	}

	/**
	 * Lists all branches or creates a new branch.
	 *
	 * @return array{branches: array<int, array{name: string, current: bool}>, output: string}
	 */
	public function gitBranch(string $newBranch = ''): array
	{
		if ('' !== $newBranch) {
			$this->validateRef($newBranch);
			$result = $this->gitCommand('branch', [$newBranch]);

			if (0 !== $result['exitCode']) {
				throw new RuntimeException("Git branch creation failed: {$result['output']}");
			}
		}

		$result = $this->gitCommand('branch', ['-a']);
		$lines = explode("\n", trim($result['output']));
		$branches = [];

		foreach ($lines as $line) {
			$line = trim($line);

			if ('' === $line) {
				continue;
			}

			$current = str_starts_with($line, '*');
			$name = trim(ltrim($line, '* '));

			$branches[] = ['name' => $name, 'current' => $current];
		}

		return ['branches' => $branches, 'output' => $result['output']];
	}

	/**
	 * Switches to a different branch.
	 *
	 * @return array{branch: string, output: string}
	 */
	public function gitCheckout(string $branch, bool $createNew = false): array
	{
		$this->validateRef($branch);

		$args = [];

		if ($createNew) {
			$args[] = '-b';
		}

		$args[] = $branch;

		$result = $this->gitCommand('checkout', $args);

		if (0 !== $result['exitCode']) {
			throw new RuntimeException("Git checkout failed: {$result['output']}");
		}

		return ['branch' => $branch, 'output' => $result['output']];
	}

	/**
	 * Merges a branch into the current branch.
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function gitMerge(string $branch, bool $noFastForward = false): array
	{
		$this->validateRef($branch);

		$args = [];

		if ($noFastForward) {
			$args[] = '--no-ff';
		}

		$args[] = $branch;

		$result = $this->gitCommand('merge', $args);

		if (0 !== $result['exitCode']) {
			throw new RuntimeException("Git merge failed (conflicts?): {$result['output']}");
		}

		return $result;
	}

	/**
	 * Stashes or applies stashed changes.
	 *
	 * @return array{output: string, exitCode: int}
	 */
	public function gitStash(string $action = 'push', string $message = ''): array
	{
		$allowedActions = ['push', 'pop', 'apply', 'list', 'drop', 'clear'];

		if (!in_array($action, $allowedActions, true)) {
			throw new InvalidArgumentException("Invalid stash action: {$action}. Allowed: " . implode(', ', $allowedActions));
		}

		$args = [$action];

		if ('push' === $action && '' !== $message) {
			$args[] = '-m';
			$args[] = $message;
		}

		$result = $this->gitCommand('stash', $args);

		if (0 !== $result['exitCode'] && 'list' !== $action) {
			throw new RuntimeException("Git stash {$action} failed: {$result['output']}");
		}

		return $result;
	}

	/**
	 * Reads the last N lines from a file (similar to tail -f).
	 *
	 * @return array{path: string, lines: array<int, string>, total: int}
	 */
	public function tailFile(string $path, int $lines = self::DEFAULT_TAIL_LINES): array
	{
		$path = FileFinder::guard($path);

		if (!file_exists($path)) {
			throw new RuntimeException("File not found: {$path}");
		}

		if (!is_file($path)) {
			throw new RuntimeException("Not a file: {$path}");
		}

		$lines = max(1, min($lines, self::MAX_TAIL_LINES));

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$rawOutput = shell_exec("tail -n {$lines} " . escapeshellarg($path) . ' 2>&1');

		$output = is_string($rawOutput) ? $rawOutput : '';

		if ('' === $output) {
			return ['path' => $path, 'lines' => [], 'total' => 0];
		}

		$result = explode("\n", $output);
		$result = array_filter($result, static fn (string $line): bool => '' !== trim($line));

		return ['path' => $path, 'lines' => array_values($result), 'total' => count($result)];
	}

	/**
	 * Lists running processes with PID, CPU, memory, and command.
	 *
	 * @return array{processes: array<int, array{pid: int, cpu: string, mem: string, command: string}>, total: int}
	 */
	public function processList(int $limit = 50): array
	{
		$limit = max(1, min($limit, self::MAX_PROCESS_LIST));

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$rawOutput = shell_exec('ps aux --sort=-%cpu | head -n ' . ($limit + 1));

		$output = is_string($rawOutput) ? $rawOutput : '';

		if ('' === $output) {
			return ['processes' => [], 'total' => 0];
		}

		$allLines = explode("\n", trim($output));
		array_shift($allLines);

		$processes = [];

		foreach ($allLines as $line) {
			if ('' === trim($line)) {
				continue;
			}

			$parts = preg_split('/\s+/', $line, 11);

			if (!is_array($parts) || count($parts) < 11) {
				continue;
			}

			$processes[] = [
				'pid' => (int) $parts[1],
				'cpu' => $parts[2],
				'mem' => $parts[3],
				'command' => $parts[10],
			];
		}

		return ['processes' => $processes, 'total' => count($processes)];
	}

	/**
	 * Terminates a process by PID.
	 *
	 * @return array{pid: int, killed: bool, signal: string}
	 */
	public function processKill(int $pid, string $signal = 'TERM'): array
	{
		$allowedSignals = ['TERM', 'KILL', 'HUP', 'INT', 'QUIT'];

		if (!in_array($signal, $allowedSignals, true)) {
			throw new InvalidArgumentException("Invalid signal: {$signal}. Allowed: " . implode(', ', $allowedSignals));
		}

		if ($pid <= 0) {
			throw new InvalidArgumentException("Invalid PID: {$pid}");
		}

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$result = shell_exec("kill -{$signal} {$pid} 2>&1");
		$killed = null === $result || false === stripos((string) $result, 'No such process');

		return ['pid' => $pid, 'killed' => $killed, 'signal' => $signal];
	}

	/**
	 * Reports disk usage statistics.
	 *
	 * @return array{filesystems: array<int, array{filesystem: string, size: string, used: string, available: string, use_pct: string, mounted: string}>, total: int}
	 */
	public function diskUsage(): array
	{
		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$rawOutput = shell_exec('df -h');

		$output = is_string($rawOutput) ? $rawOutput : '';

		if (!$output) {
			return ['filesystems' => [], 'total' => 0];
		}

		$allLines = explode("\n", trim($output));
		array_shift($allLines);

		$filesystems = [];

		foreach ($allLines as $line) {
			if ('' === trim($line)) {
				continue;
			}

			$parts = preg_split('/\s+/', $line, 6);

			if (!is_array($parts) || count($parts) < 6) {
				continue;
			}

			$filesystems[] = [
				'filesystem' => $parts[0],
				'size' => $parts[1],
				'used' => $parts[2],
				'available' => $parts[3],
				'use_pct' => $parts[4],
				'mounted' => $parts[5],
			];
		}

		return ['filesystems' => $filesystems, 'total' => count($filesystems)];
	}

	/**
	 * Tests network connectivity via ping.
	 *
	 * @return array{host: string, reachable: bool, latency: string, output: string}
	 */
	public function networkTest(string $host, int $count = 4): array
	{
		$host = escapeshellarg($host);
		$count = max(1, min($count, self::MAX_PING_COUNT));

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$rawOutput = shell_exec("ping -c {$count} {$host} 2>&1");

		$output = is_string($rawOutput) ? $rawOutput : '';

		$reachable = false !== stripos($output, 'bytes from');
		$latency = '';

		if (preg_match('/avg = ([\d\.]+)/', $output, $matches)) {
			$latency = $matches[1] . ' ms';
		}

		return [
			'host' => trim($host, "'"),
			'reachable' => $reachable,
			'latency' => $latency,
			'output' => $output,
		];
	}

	/**
	 * Executes a command inside the project root directory.
	 *
	 * Output from stdout and stderr is capped at {@see MAX_OUTPUT_BYTES} to
	 * prevent runaway child processes from exhausting the container's memory.
	 *
	 * @param string $command fully constructed shell command
	 * @param bool $throw when true, throws RuntimeException on non-zero exit
	 *
	 * @return array{output: string, exitCode: int}
	 *
	 * @throws RuntimeException when $throw is true and the exit code is non-zero
	 */
	public function run(string $command, bool $throw = false): array
	{
		$root = (string) Path::getRootDir();

		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		$process = proc_open($command, $descriptors, $pipes, $root);

		if (!is_resource($process)) {
			if ($throw) {
				throw new RuntimeException("Shell command failed [-1]: {$command}\nproc_open failed");
			}

			return ['output' => '', 'exitCode' => -1];
		}

		fclose($pipes[0]);

		$stdout = stream_get_contents($pipes[1], self::MAX_OUTPUT_BYTES) ?: '';
		$stderr = stream_get_contents($pipes[2], self::MAX_OUTPUT_BYTES) ?: '';

		fclose($pipes[1]);
		fclose($pipes[2]);

		$exitCode = proc_close($process);
		$output = trim($stdout . $stderr);

		if ($throw && 0 !== $exitCode) {
			throw new RuntimeException(
				"Shell command failed [{$exitCode}]: {$command}" . ('' !== $output ? "\n{$output}" : '')
			);
		}

		return ['output' => $output, 'exitCode' => $exitCode];
	}

	private function validateRef(string $ref): void
	{
		if (1 !== preg_match(self::SAFE_REF_PATTERN, $ref)) {
			throw new InvalidArgumentException("Invalid git ref: {$ref}. Only alphanumeric, dash, underscore, dot, and slash allowed.");
		}
	}
}
