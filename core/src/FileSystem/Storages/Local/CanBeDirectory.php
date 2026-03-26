<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem\Storages\Local;

/**
 * Provides recursive directory removal and copy operations for the Local storage driver.
 *
 * Both operations traverse a directory tree depth-first, operating on files first
 * and then on their parent directories. The "." and ".." scandir entries are always
 * excluded to prevent infinite recursion.
 *
 * Permissions are forced to 0777 before removal to avoid failures on read-only nodes.
 */
trait CanBeDirectory
{
	/**
	 * Recursively removes the node at $path.
	 *
	 * For plain files, unlink() is called directly. For directories, every child is
	 * removed recursively before the directory itself is deleted with rmdir().
	 * Returns false when the path does not exist or scandir() fails.
	 */
	private function removeRecursive(string $path): bool
	{
		if (!file_exists($path)) {
			return false;
		}

		chmod($path, 0777);

		if (is_file($path)) {
			return unlink($path);
		}

		$content = scandir($path);

		if (!$content) {
			return false;
		}

		unset($content[0], $content[1]);

		set($content)->each(fn (string $item) => $this->removeRecursive("$path/$item"));

		return rmdir($path);
	}

	/**
	 * Recursively copies the node at $src to $dst.
	 *
	 * For plain files, copy() is used. For directories, the destination is created
	 * and each child is copied recursively. Returns false when scandir() fails on a
	 * source directory. Parent directories of $dst are created automatically.
	 */
	private function copyRecursive(string $src, string $dst): bool
	{
		mkdir(directory: dirname($dst), recursive: true);

		if (is_file($src)) {
			return copy($src, $dst);
		}

		mkdir(directory: $dst);

		$content = scandir($src);

		if (!$content) {
			return false;
		}

		unset($content[0], $content[1]);

		set($content)->each(fn (string $item) => $this->copyRecursive("$src/$item", "$dst/$item"));

		return true;
	}
}
