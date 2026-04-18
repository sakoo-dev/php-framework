<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when writing the patched content back to storage fails. Distinct
 * from MalformedDiffException — the diff was valid and applied cleanly in
 * memory, but the underlying write to disk returned false.
 */
final class PatchWriteException extends Exception
{
	public static function forPath(string $path): self
	{
		return new self("Failed to write patched content to: {$path}");
	}
}
