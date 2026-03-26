<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem\Storages\Local;

/**
 * Provides exclusive-lock file write operations for the Local storage driver.
 *
 * Uses fopen/flock/fwrite/fflush/fclose to guarantee that concurrent processes
 * do not corrupt file contents through interleaved writes. An exclusive lock
 * (LOCK_EX) is acquired before writing and released immediately after flushing.
 */
trait CanBeWritable
{
	/**
	 * Opens the file at $this->path with $mode ('w' to overwrite, 'a' to append),
	 * acquires an exclusive lock, writes $data, flushes OS buffers, then releases
	 * the lock and closes the handle.
	 *
	 * The parent directory is created via mkdir() before opening so the file can be
	 * written even when the directory tree does not yet exist.
	 *
	 * Returns true when at least one byte was written, false on any failure
	 * (fopen failure, lock failure, or zero-byte write).
	 */
	private function writeToFile(string $data, string $mode): bool
	{
		$this->mkdir();
		$file = fopen($this->path, $mode);
		$result = false;

		if (!$file) {
			return $result;
		}

		if (flock($file, LOCK_EX)) {
			$result = fwrite($file, $data);
			fflush($file);
			flock($file, LOCK_UN);
		}

		fclose($file);

		return (bool) $result;
	}
}
