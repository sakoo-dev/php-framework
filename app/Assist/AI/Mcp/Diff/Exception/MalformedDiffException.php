<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a unified diff supplied to the patch applier is structurally
 * invalid or does not match the target file: unparseable hunk header, missing
 * hunks, body lines before any @@ header, an empty body line with no sigil,
 * or context/removed lines that do not match the file content at the claimed
 * offset.
 *
 * Distinct from a write failure — the diff itself was malformed or stale and
 * the caller should regenerate it from current file state rather than retry.
 */
final class MalformedDiffException extends Exception
{
	public static function invalidHeader(string $header): self
	{
		return new self("Unparseable hunk header: {$header}");
	}

	public static function noHunks(): self
	{
		return new self('Diff contains no parseable hunks.');
	}

	public static function bodyBeforeHeader(): self
	{
		return new self('Diff body line appeared before any hunk header.');
	}

	public static function emptyBodyLine(): self
	{
		return new self('Diff contains an empty body line with no sigil. Context lines must start with a single space.');
	}

	public static function startLineOutOfRange(int $startLine, int $totalLines): self
	{
		return new self("Hunk start line {$startLine} is outside the file (file has {$totalLines} lines).");
	}

	public static function contextMismatch(int $lineNumber, string $expected, string $actual): self
	{
		return new self(sprintf(
			"Hunk does not match file at line %d.\n  expected: %s\n  actual:   %s",
			$lineNumber,
			self::truncate($expected),
			self::truncate($actual),
		));
	}

	private static function truncate(string $line): string
	{
		$line = rtrim($line, "\r\n");

		return mb_strlen($line) > 120 ? mb_substr($line, 0, 117) . '...' : $line;
	}
}
