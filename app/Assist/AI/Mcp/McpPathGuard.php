<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Exception\InvalidArgumentException;
use System\Path\Path;

/**
 * Validates and resolves file paths to ensure they stay inside the project root.
 *
 * Prevents path-traversal attacks (e.g. `../../etc/passwd`) and restricts all
 * MCP file operations to the project directory tree. Every MCP tool that accepts
 * a path argument MUST call {@see guard()} before touching the filesystem.
 *
 * Resolution strategy:
 *   1. Relative paths are prefixed with the project root.
 *   2. realpath() is used when the target exists (catches symlink escapes).
 *   3. Manual normalisation strips `.` and `..` segments for new files.
 *   4. The resolved absolute path must start with the project root prefix.
 *
 * @see McpElements  All file/directory tools delegate to this guard.
 * @see InvalidArgumentException Thrown when a path escapes the project scope.
 */
final class McpPathGuard
{
	/**
	 * Resolves $path to an absolute path and asserts it lives under the project root.
	 *
	 * @param string $path relative or absolute filesystem path
	 *
	 * @return string the resolved absolute path guaranteed to be within the project
	 *
	 * @throws InvalidArgumentException when the resolved path escapes the project root
	 */
	public static function guard(string $path): string
	{
		$root = (string) Path::getRootDir();

		if ('' === $root) {
			throw new InvalidArgumentException('Cannot resolve project root directory.');
		}

		$root = rtrim($root, '/');

		if (!str_starts_with($path, '/')) {
			$path = $root . '/' . $path;
		}

		$resolved = self::normalisePath($path);

		if (!str_starts_with($resolved, $root . '/') && $resolved !== $root) {
			throw new InvalidArgumentException("Path escapes project scope: {$path}");
		}

		return $resolved;
	}

	/**
	 * Guards an array of paths and returns the resolved array.
	 *
	 * @param string[] $paths list of relative or absolute paths
	 *
	 * @return string[] resolved absolute paths
	 *
	 * @throws InvalidArgumentException when any path escapes the project root
	 */
	public static function guardMany(array $paths): array
	{
		return array_map(static fn (string $p): string => self::guard($p), $paths);
	}

	/**
	 * Resolves `.` and `..` segments in $path.
	 *
	 * Uses {@see realpath()} when the target exists on disk (catches symlink
	 * escapes). Falls back to manual string-based normalisation for files
	 * that do not yet exist (e.g. write targets).
	 *
	 * @param string $path Absolute path with potential `.`/`..` segments.
	 *
	 * @return string normalised absolute path
	 */
	private static function normalisePath(string $path): string
	{
		$real = realpath($path);

		if (false !== $real) {
			return $real;
		}

		$parts = explode('/', $path);
		$resolved = [];

		foreach ($parts as $part) {
			if ('.' === $part || '' === $part) {
				continue;
			}

			if ('..' === $part) {
				array_pop($resolved);
			} else {
				$resolved[] = $part;
			}
		}

		return '/' . implode('/', $resolved);
	}
}
