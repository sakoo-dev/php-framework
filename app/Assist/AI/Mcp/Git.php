<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

/**
 * Structured git operations with parsed results.
 *
 * Wraps {@see Shell::git()} calls and parses their raw output into
 * typed arrays ready for MCP tool consumption. All path arguments must
 * be pre-validated by the caller via {@see McpPathGuard}.
 *
 * @see Shell      Low-level command executor.
 * @see McpElements Sole consumer of this class.
 */
final class Git
{
	public function __construct(private readonly Shell $shell) {}

	/**
	 * Returns recent commits as structured arrays.
	 *
	 * @param int    $limit maximum commits (hard max: 200)
	 * @param string $path  optional file path filter (must be pre-guarded)
	 *
	 * @return array{commits: array<array{hash: string, msg: string}>, n: int}
	 */
	public function log(int $limit = 20, string $path = ''): array
	{
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';
		$limit = min($limit, 200);

		['output' => $output] = $this->shell->git("log --format='%h|%s' --no-decorate -n {$limit}{$pathArg}");

		$lines = array_filter(explode("\n", trim($output)));

		$commits = array_map(static function (string $line): array {
			$parts = explode('|', $line, 2);

			return ['hash' => $parts[0], 'msg' => $parts[1] ?? ''];
		}, $lines);

		return ['commits' => $commits, 'n' => count($commits)];
	}

	/**
	 * Returns a parsed git diff with optional line truncation.
	 *
	 * @param string $ref      git ref to diff against (empty = unstaged)
	 * @param string $path     optional file path filter (must be pre-guarded)
	 * @param int    $maxLines maximum lines to return (hard max: 1000)
	 *
	 * @return array{diff: string, lines: int, truncated: bool}
	 */
	public function diff(string $ref = '', string $path = '', int $maxLines = 200): array
	{
		$refArg = '' !== $ref ? ' ' . escapeshellarg($ref) : '';
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';
		$maxLines = min($maxLines, 1000);

		['output' => $raw] = $this->shell->git("diff{$refArg}{$pathArg}");

		$lines = explode("\n", trim($raw));
		$total = count($lines);
		$truncated = $total > $maxLines;

		if ($truncated) {
			$lines = array_slice($lines, 0, $maxLines);
		}

		return ['diff' => implode("\n", $lines), 'lines' => $total, 'truncated' => $truncated];
	}

	/**
	 * Returns parsed porcelain status with summary counts.
	 *
	 * @param string $path optional file path filter (must be pre-guarded)
	 *
	 * @return array{clean: bool, files: array<array{status: string, file: string}>, summary: string}
	 */
	public function status(string $path = ''): array
	{
		$pathArg = '' !== $path ? ' -- ' . escapeshellarg($path) : '';

		['output' => $raw] = $this->shell->git("status --porcelain{$pathArg}");

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

		$summary = "staged:{$staged} modified:{$modified} untracked:{$untracked}";

		return ['clean' => false, 'files' => $files, 'summary' => $summary];
	}
}
