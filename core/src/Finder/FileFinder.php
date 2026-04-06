<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use System\Path\Path;

/**
 * Recursive PHP-file finder with configurable filtering options.
 *
 * FileFinder walks a directory tree and collects files whose names match a glob
 * pattern. Filtering options can be combined freely via a fluent builder API:
 *
 * - pattern()         — restricts results to filenames matching a glob (default: '*').
 * - ignoreVCS()       — skips directories used by common VCS systems (.git, .svn, .hg, .bzr).
 * - ignoreVCSIgnored()— skips files that would be excluded by the nearest .gitignore.
 * - ignoreDotFiles()  — skips any file or directory whose name begins with '.'.
 * - limit()           — caps the number of returned results.
 *
 * Static path-guarding methods ({@see guard()}, {@see guardMany()}) validate and
 * resolve filesystem paths to ensure they stay inside the project root, preventing
 * path-traversal attacks (e.g. `../../etc/passwd`).
 *
 * getFiles() returns an array of SplFileObject instances ready for further inspection,
 * while find() returns raw pathname strings for callers that need the paths only.
 *
 * The class is declared final to prevent extension; filtering behaviour should be
 * modified by composing FileFinder instances rather than subclassing.
 */
final class FileFinder
{
	private string $pattern = '*';
	private bool $ignoreVCS = false;
	private bool $ignoreVCSIgnored = false;
	private bool $ignoreDotFiles = false;
	private int $limit = 0;
	private bool $truncated = false;

	private const VCS_SYSTEMS = ['.git', '.svn', '.hg', '.bzr'];

	public function __construct(private readonly string $path) {}

	/**
	 * Resolves $path to an absolute path and asserts it lives under the project root.
	 *
	 * Resolution strategy:
	 *   1. Relative paths are prefixed with the project root.
	 *   2. realpath() is used when the target exists (catches symlink escapes).
	 *   3. For non-existent targets, the nearest existing parent is resolved with
	 *      realpath() to catch symlink escapes before appending missing segments.
	 *   4. Manual normalisation strips `.` and `..` segments when no parent exists.
	 *   5. The resolved absolute path must start with the project root prefix.
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

		$root = realpath($root) ?: $root;
		$root = rtrim($root, '/');

		if (!str_starts_with($path, '/')) {
			$path = $root . '/' . $path;
		}

		$resolved = self::resolvePathWithinRoot($path);

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
	 * Restricts results to files whose names match the given glob $pattern.
	 * Defaults to '*' (all files) when not called.
	 */
	public function pattern(string $pattern): FileFinder
	{
		$this->pattern = $pattern;

		return $this;
	}

	/**
	 * When $value is true (the default), directories used by VCS systems
	 * (.git, .svn, .hg, .bzr) are excluded from traversal entirely.
	 */
	public function ignoreVCS(bool $value = true): FileFinder
	{
		$this->ignoreVCS = $value;

		return $this;
	}

	/**
	 * When $value is true (the default), files matched by the nearest .gitignore
	 * are excluded from results. Requires a readable .gitignore in the working directory.
	 */
	public function ignoreVCSIgnored(bool $value = true): FileFinder
	{
		$this->ignoreVCSIgnored = $value;

		return $this;
	}

	/**
	 * When $value is true (the default), any file or directory whose name starts
	 * with '.' is excluded from traversal and results.
	 */
	public function ignoreDotFiles(bool $value = true): FileFinder
	{
		$this->ignoreDotFiles = $value;

		return $this;
	}

	/**
	 * Caps the number of results returned by find() and getFiles().
	 * A value of 0 (the default) means no limit.
	 */
	public function limit(int $limit): FileFinder
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Returns whether the result set was truncated by the configured limit.
	 * Only meaningful after calling find() or getFiles().
	 */
	public function isLimited(): bool
	{
		return $this->limit > 0;
	}

	/**
	 * Returns whether the last find() call was truncated by the configured limit.
	 * Only meaningful after calling find() or getFiles().
	 */
	public function wasTruncated(): bool
	{
		return $this->truncated;
	}

	/**
	 * Executes the search and returns all matching files as SplFileObject instances
	 * opened in read-write ('r+') mode.
	 *
	 * @return SplFileObject[]
	 */
	public function getFiles(): array
	{
		return array_map(fn (string $file) => new SplFileObject($file, 'r+'), $this->find());
	}

	/**
	 * Executes the search and returns the absolute pathnames of all matching files
	 * as plain strings, applying all configured filters during traversal.
	 *
	 * @return string[]
	 */
	public function find(): array
	{
		Assert::dir($this->path, "The path '{$this->path}' is not a valid directory.");

		$this->truncated = false;

		$directory = new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::FOLLOW_SYMLINKS);
		$filter = new \RecursiveCallbackFilterIterator($directory, fn (\SplFileInfo $file) => $this->shouldDescend($file));
		$iterator = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);

		$files = [];

		foreach ($iterator as $file) {
			/** @var \SplFileInfo $file */
			if ($this->shouldSkip($file)) {
				continue;
			}

			if ($file->isFile()) {
				$files[] = $file->getPathname();

				if ($this->limit > 0 && count($files) >= $this->limit) {
					$this->truncated = true;

					break;
				}
			}
		}

		return $files;
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

	/**
	 * Resolves an absolute path while preserving symlink safety for non-existent files.
	 *
	 * When $path does not exist yet, resolves the nearest existing ancestor via
	 * realpath() and appends the missing suffix. This catches cases where an in-root
	 * directory is a symlink that points outside the project.
	 */
	private static function resolvePathWithinRoot(string $path): string
	{
		$resolved = self::normalisePath($path);
		$cursor = $resolved;
		$suffix = [];

		while (!file_exists($cursor)) {
			$parent = dirname($cursor);

			if ($parent === $cursor) {
				return $resolved;
			}

			array_unshift($suffix, basename($cursor));
			$cursor = $parent;
		}

		$realExisting = realpath($cursor);

		if (false === $realExisting) {
			return $resolved;
		}

		$rebuilt = rtrim($realExisting, '/');

		if ([] !== $suffix) {
			$rebuilt .= '/' . implode('/', $suffix);
		}

		return '' === $rebuilt ? '/' : $rebuilt;
	}

	private function shouldSkip(\SplFileInfo $file): bool
	{
		$name = $file->getFilename();

		if ($this->ignoreDotFiles && str_starts_with($name, '.')) {
			return true;
		}

		if ($this->ignoreVCSIgnored && (new GitIgnore())->isIgnored($file->getPathname())) {
			return true;
		}

		if (!fnmatch($this->pattern, $name)) {
			return true;
		}

		return false;
	}

	/**
	 * Callback for RecursiveCallbackFilterIterator that decides whether to descend
	 * into a directory. VCS directories are pruned here (when ignoreVCS is active)
	 * to avoid traversing potentially large and irrelevant directory trees.
	 * Regular files always return true so the outer loop can apply its own filters.
	 */
	private function shouldDescend(\SplFileInfo $file): bool
	{
		if (!$file->isDir()) {
			return true;
		}

		$name = $file->getFilename();

		if ($this->ignoreVCS && in_array($name, self::VCS_SYSTEMS)) {
			return false;
		}

		if ($this->ignoreDotFiles && str_starts_with($name, '.')) {
			return false;
		}

		return true;
	}
}
