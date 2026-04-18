<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a unified diff supplied to the patch applier is structurally
 * invalid: unparseable hunk header, missing hunks entirely, or body lines
 * that appear before any @@ header.
 *
 * Distinct from a write failure — the diff itself was malformed and the
 * caller should regenerate it rather than retry.
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
}
