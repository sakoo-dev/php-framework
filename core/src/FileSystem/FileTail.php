<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

/**
 * Immutable value object representing trailing lines read from a file.
 *
 * Returned by Storage::readTail() to replace the former unstructured array.
 * Lines are in reverse-chronological order (newest first).
 */
final readonly class FileTail
{
	/**
	 * @phpstan-param string[] $lines
	 */
	public function __construct(
		public array $lines,
	) {}
}
