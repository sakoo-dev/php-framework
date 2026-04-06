<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

/**
 * Immutable value object representing a slice of lines read from a file.
 *
 * Returned by {@see Storage::readChunk()} to replace the former unstructured
 * associative array. Each property maps 1-to-1 to the previous array keys:
 *
 *   'content'    → $content
 *   'totalLines' → $totalLines
 *   'from'       → $from
 *   'to'         → $to
 *   'truncated'  → $truncated
 */
final readonly class FileChunk
{
	public function __construct(
		public string $content,
		public int $totalLines,
		public int $from,
		public int $to,
		public bool $truncated,
	) {}
}
