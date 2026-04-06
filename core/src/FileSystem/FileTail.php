<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

/**
 * Immutable value object representing trailing lines read from a file.
 *
 * Returned by {@see Storage::readTail()} to replace the former unstructured
 * array. Lines are in reverse-chronological order (newest first).
 */
final readonly class FileTail
{
	/**
	 * @param string[] $lines trailing lines in reverse order (newest first)
	 */
	public function __construct(
		public array $lines,
	) {}
}
