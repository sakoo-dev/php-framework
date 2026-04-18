<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff;

use App\Assist\AI\Mcp\Diff\Exception\MalformedDiffException;

/**
 * Parses and represents the header line of a unified-diff hunk.
 *
 * A hunk header has the form:  @@ -<origStart>[,<origCount>] +<newStart>[,<newCount>] @@
 *
 * Only the original-file start line is needed to splice the hunk back into the
 * source, so that is the only field exposed. Isolating header parsing here keeps
 * DiffHunk focused on its value-object role (what changes, not how the format
 * announces them).
 */
final readonly class DiffHeader
{
	/**
	 * Unified-diff hunk header pattern. The named capture group `start` extracts
	 * the 1-based original-file line number where the hunk begins.
	 */
	private const PATTERN = '/^@@ -(?P<start>\d+)(?:,\d+)? \+\d+(?:,\d+)? @@/';

	public function __construct(public int $startLine) {}

	/**
	 * @throws MalformedDiffException when the line does not match the unified-diff hunk header format
	 */
	public static function parse(string $line): self
	{
		$matched = preg_match(self::PATTERN, $line, $matches);

		if (1 !== $matched) {
			throw MalformedDiffException::invalidHeader($line);
		}

		return new self(startLine: (int) $matches['start']);
	}

	public static function isHeader(string $line): bool
	{
		return str_starts_with($line, '@@');
	}
}
