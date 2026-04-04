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
 *   - All user-supplied arguments are escaped via {@see escapeshellarg()}.
 *   - STDERR is merged into STDOUT so errors are never silently lost.
 *   - Git commands are scoped via `git -C {root}` automatically.
 *   - The `sakoo()` method enforces a sub-command allowlist.
 *
 * Uses {@see proc_open()} internally for reliable stdout+stderr capture.
 *
 * @see McpElements            The sole consumer of this class.
 * @see InvalidArgumentException Thrown on disallowed sub-commands.
 * @see RuntimeException         Thrown on process failures when $throw is true.
 */
final class Shell
{
	/** Maximum bytes to read from a child process stdout/stderr to prevent OOM. */
	private const MAX_OUTPUT_BYTES = 1_048_576;

	/** Sub-commands allowed by {@see sakoo()}. */
	private const SAKOO_ALLOWED = ['assist', 'composer', 'npm'];

	/** Read-only git sub-commands allowed by {@see git()}. */
	private const GIT_ALLOWED = ['log', 'diff', 'status', 'rev-parse', 'show', 'branch'];

	/**
	 * Runs a git sub-command scoped to the project root.
	 *
	 * Only read-only git operations are permitted. The sub-command name is
	 * validated against a fixed allowlist before execution.
	 *
	 * @param string $subCommand git sub-command with arguments (e.g. "log -n 10")
	 *
	 * @return array{output: string, exitCode: int}
	 *
	 * @throws InvalidArgumentException when the git sub-command is not in the allowlist
	 */
	public function git(string $subCommand): array
	{
		$parts = preg_split('/\s+/', trim($subCommand), 2);
		$verb = $parts[0] ?? '';

		if (!in_array($verb, self::GIT_ALLOWED, true)) {
			throw new InvalidArgumentException("Git sub-command not allowed: {$verb}. Allowed: " . implode(', ', self::GIT_ALLOWED));
		}

		$root = (string) Path::getRootDir();

		return $this->run('git -C ' . escapeshellarg($root) . ' ' . $subCommand);
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
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';

		['output' => $output] = $this->git("log --format='%h|%s' --no-decorate -n {$limit}{$pathArg}");

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
		$refArg = '' !== $ref ? ' ' . escapeshellarg($ref) : '';
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';
		$maxLines = min($maxLines, 1000);

		['output' => $raw] = $this->git("diff{$refArg}{$pathArg}");

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
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';

		['output' => $raw] = $this->git("status --porcelain{$pathArg}");

		$raw = trim($raw);

		if ('' === $raw) {
			return ['clean' => true, 'files' => [], 'summary' => 'Working tree clean'];
		}

		$lines = array_filter(explode("\n", $raw));
		$files = [];

		foreach ($lines as $line) {
			$status = trim(substr($line, 0, 2));
			$file = trim(substr($line, 3));
			$files[] = ['status' => $status, 'file' => $file];
		}

		$staged = count(array_filter($files, fn (array $f): bool => !str_starts_with($f['status'], '?') && !str_starts_with($f['status'], ' ')));
		$untracked = count(array_filter($files, fn (array $f): bool => '??' === $f['status']));
		$modified = count($files) - $staged - $untracked;

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
	 * spawning when check_code runs the full suite (integration tests spawn
	 * nested PHPUnit/PHPStan/CS-Fixer child processes).
	 *
	 * @param string $filter PHPUnit --filter value (class or method name); empty = run all
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
	 * @return array{ok: bool, summary: string, output: string}
	 */
	public function phpunitParsed(string $filter = ''): array
	{
		['output' => $output] = $this->phpunit($filter);

		return ['output' => $output] + self::parsePhpunitOutput($output);
	}

	/**
	 * Runs PHPStan static analysis.
	 *
	 * Uses a conservative memory limit to avoid OOM in constrained containers.
	 * PHPStan paths are already scoped in phpstan.neon (app + core/src).
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
	 * @return array{ok: bool, output: string}
	 */
	public function phpstanParsed(): array
	{
		['output' => $output] = $this->phpstan();

		return ['ok' => self::parsePhpstanOutput($output), 'output' => $output];
	}

	/**
	 * Runs PHP-CS-Fixer in check or fix mode.
	 *
	 * Scoped explicitly to app/ and core/src to avoid scanning vendor/ and
	 * other large directories that cause OOM in constrained containers.
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
	 * @return array{ok: bool, output: string}
	 */
	public function phpCsFixerParsed(bool $fix = false): array
	{
		['output' => $output] = $this->phpCsFixer($fix);

		return ['ok' => self::parseCsFixerOutput($output), 'output' => $output];
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
		$parts = explode(' ', trim($command), 2);
		$sub = $parts[0];

		if (!in_array($sub, self::SAKOO_ALLOWED, true)) {
			throw new InvalidArgumentException('Sakoo sub-command not allowed: ' . $sub . '. Allowed: ' . implode(', ', self::SAKOO_ALLOWED));
		}

		return $this->run(escapeshellcmd($command));
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
