<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Exception\InvalidArgumentException;
use Mcp\Exception\RuntimeException;
use System\Path\Path;

/**
 * Project-scoped shell command executor with a closed command vocabulary.
 *
 * Every executable command is exposed as a named method with typed parameters.
 * There is no public method that accepts an arbitrary command string — the
 * attack surface is limited to the specific operations listed here.
 *
 * Security constraints:
 *   - No public generic exec(). All commands are pre-defined methods.
 *   - Working directory is always the project root (never configurable).
 *   - Structured git helpers escape every argument via {@see escapeshellarg()}.
 *   - Public git() input is constrained to a safe character allowlist.
 *   - STDERR is merged into STDOUT so errors are never silently lost.
 *   - Git commands are scoped via `git -C {root}` automatically.
 *   - The `sakoo()` method enforces a sub-command allowlist.
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
	 * @param int    $limit maximum commits (hard max: 200)
	 * @param string $path  optional file path filter (already guarded by caller)
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
	 * @param string $ref      git ref to diff against (empty = unstaged)
	 * @param string $path     optional file path filter (already guarded by caller)
	 * @param int    $maxLines maximum lines (hard max: 1000)
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
	 * @param string   $verb git sub-command verb
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
	 * Executes a command inside the project root directory.
	 *
	 * Output from stdout and stderr is capped at {@see MAX_OUTPUT_BYTES} to
	 * prevent runaway child processes from exhausting the container's memory.
	 *
	 * @param string $command fully constructed shell command
	 * @param bool   $throw   when true, throws RuntimeException on non-zero exit
	 *
	 * @return array{output: string, exitCode: int}
	 *
	 * @throws RuntimeException when $throw is true and the exit code is non-zero
	 */
	private function run(string $command, bool $throw = false): array
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
}
