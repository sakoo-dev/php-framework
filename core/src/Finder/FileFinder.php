<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\Finder\DTO\FileMetadata;
use Sakoo\Framework\Core\Finder\DTO\FindResult;
use Sakoo\Framework\Core\Finder\DTO\GrepMatch;
use Sakoo\Framework\Core\Finder\DTO\GrepResult;
use System\Path\Path;

/**
 * Recursive file finder with pattern matching, content search, and metadata extraction.
 *
 * FileFinder provides three core capabilities:
 *
 * 1. **File Discovery** — walks a directory tree and collects files whose names match
 *    a glob pattern. Filtering options can be combined via a fluent builder API:
 *    - pattern()          — restricts results to filenames matching a glob (default: '*').
 *    - ignoreVCS()        — skips directories used by common VCS systems (.git, .svn, .hg, .bzr).
 *    - ignoreVCSIgnored() — skips files that would be excluded by the nearest .gitignore.
 *    - ignoreDotFiles()   — skips any file or directory whose name begins with '.'.
 *    - limit()            — caps the number of returned results.
 *
 * 2. **Content Search** — grep() searches file contents for a pattern using case-insensitive
 *    substring matching. Returns structured results with file paths, line numbers, and matched text.
 *
 * 3. **File Metadata** — metadata() returns size, modification time, permissions, and
 *    read/write flags for a given file.
 *
 * Static path-guarding methods (guard(), guardMany()) validate and resolve filesystem
 * paths to ensure they stay inside the project root, preventing path-traversal attacks.
 *
 * All search operations automatically skip vendor, node_modules, .git, storage, and .idea
 * directories, and respect file size limits to avoid memory exhaustion.
 */
final class FileFinder
{
	private const int MAX_GREP_RESULTS = 500;
	private const int MAX_FIND_RESULTS = 200;
	private const int MAX_FILE_SIZE_BYTES = 1_048_576;

	private const array VCS_SYSTEMS = ['.git', '.svn', '.hg', '.bzr'];
	private const array SKIP_DIRS = ['vendor', 'node_modules', '.git', 'storage', '.idea'];

	private string $pattern = '*';
	private bool $ignoreVCS = false;
	private bool $ignoreVCSIgnored = false;
	private bool $ignoreDotFiles = false;
	private int $limit = 0;
	private bool $truncated = false;

	/**
	 * Constructs a FileFinder rooted at $path.
	 */
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
	 * @return string the resolved absolute path guaranteed to be within the project
	 *
	 * @throws InvalidArgumentException when the resolved path escapes the project root
	 */
	public static function guard(string $path): string
	{
		$root = (string) Path::getRootDir();
		throwUnless((bool) $root, new InvalidArgumentException('Cannot resolve project root directory.'));

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
	 * Searches file contents for a pattern using case-insensitive substring matching.
	 *
	 * Recursively walks the directory tree starting from $path, reading each file
	 * and returning lines that contain the search pattern. Automatically skips
	 * vendor, node_modules, .git, storage, and .idea directories, and files larger
	 * than 1MB.
	 *
	 * @param string $pattern substring to search for (case-insensitive)
	 * @param string $path root directory for search (defaults to project root)
	 * @param int $limit maximum number of matches to return (capped at 500)
	 *
	 * @throws \RuntimeException when the search path is invalid or inaccessible
	 */
	public static function grep(string $pattern, string $path = '', int $limit = 100): GrepResult
	{
		$limit = min($limit, self::MAX_GREP_RESULTS);
		$searchPath = !$path ? (string) Path::getRootDir() : self::guard($path);

		if (!is_dir($searchPath)) {
			$searchPath = dirname($searchPath);
		}

		$matches = [];
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($searchPath, \FilesystemIterator::SKIP_DOTS),
		);

		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile() || self::shouldSkipPath($file->getPathname())) {
				continue;
			}

			if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
				continue;
			}

			$lines = file($file->getPathname());

			if (!$lines) {
				continue;
			}

			foreach ($lines as $lineNo => $lineText) {
				if (false !== stripos($lineText, $pattern)) {
					$matches[] = new GrepMatch(self::relativePath($file->getPathname()), $lineNo + 1, trim($lineText));

					if (count($matches) >= $limit) {
						break 2;
					}
				}
			}
		}

		return new GrepResult($pattern, $matches, count($matches), count($matches) >= $limit);
	}

	/**
	 * Finds files by name pattern using glob-style wildcards.
	 *
	 * Recursively walks the directory tree starting from $path, matching file names
	 * against the provided glob pattern using fnmatch(). The match is case-insensitive.
	 * Automatically skips vendor, node_modules, .git, storage, and .idea directories.
	 *
	 * @param string $pattern glob pattern (e.g. '*.php', 'test_*.txt')
	 * @param string $path root directory for search (defaults to project root)
	 * @param int $limit maximum number of files to return (capped at 200)
	 *
	 * @throws \RuntimeException when the search path is invalid or inaccessible
	 */
	public static function search(string $pattern, string $path = '', int $limit = 100): FindResult
	{
		$limit = min($limit, self::MAX_FIND_RESULTS);
		$searchPath = !$path ? (string) Path::getRootDir() : self::guard($path);

		if (!is_dir($searchPath)) {
			$searchPath = dirname($searchPath);
		}

		$files = [];
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($searchPath, \FilesystemIterator::SKIP_DOTS),
		);

		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile() || self::shouldSkipPath($file->getPathname())) {
				continue;
			}

			$fileName = $file->getFilename();

			if (fnmatch($pattern, $fileName, FNM_CASEFOLD)) {
				$files[] = self::relativePath($file->getPathname());

				if (count($files) >= $limit) {
					break;
				}
			}
		}

		return new FindResult($pattern, $files, count($files), count($files) >= $limit);
	}

	/**
	 * Gets file metadata (size, modified time, permissions, read/write flags).
	 *
	 * @throws \RuntimeException when the file does not exist or cannot be stat'd
	 */
	public static function metadata(string $path): FileMetadata
	{
		$path = self::guard($path);
		throwUnless(file_exists($path), new \RuntimeException("File not found: {$path}"));
		$stat = stat($path);
		throwIf(!$stat, new \RuntimeException("Failed to stat file: {$path}"));

		// @phpstan-ignore-next-line
		return new FileMetadata(self::relativePath($path), $stat['size'], $stat['mtime'], substr(sprintf('%o', fileperms($path)), -4), is_readable($path), is_writable($path));
	}

	/**
	 * Restricts results to files whose names match the given glob $pattern.
	 * Defaults to '*' (all files) when not called.
	 */
	public function pattern(string $pattern): self
	{
		$this->pattern = $pattern;

		return $this;
	}

	/**
	 * When $value is true (the default), directories used by VCS systems
	 * (.git, .svn, .hg, .bzr) are excluded from traversal entirely.
	 */
	public function ignoreVCS(bool $value = true): self
	{
		$this->ignoreVCS = $value;

		return $this;
	}

	/**
	 * When $value is true (the default), files matched by the nearest .gitignore
	 * are excluded from results. Requires a readable .gitignore in the working directory.
	 */
	public function ignoreVCSIgnored(bool $value = true): self
	{
		$this->ignoreVCSIgnored = $value;

		return $this;
	}

	/**
	 * When $value is true (the default), any file or directory whose name starts
	 * with '.' is excluded from traversal and results.
	 */
	public function ignoreDotFiles(bool $value = true): self
	{
		$this->ignoreDotFiles = $value;

		return $this;
	}

	/**
	 * Caps the number of results returned by find() and getFiles().
	 * A value of 0 (the default) means no limit.
	 */
	public function limit(int $limit): self
	{
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Returns whether a result limit is active (i.e. limit() was called with a
	 * positive value). Does not indicate whether the limit was actually reached.
	 */
	public function isLimited(): bool
	{
		return $this->limit > 0;
	}

	/**
	 * Returns true when the last find() or getFiles() call was cut short because
	 * the result count reached the configured limit.
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
		$iterator = new \RecursiveIteratorIterator($filter);

		$files = [];

		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile()) {
				continue;
			}

			if ($this->shouldSkip($file)) {
				continue;
			}

			$files[] = $file->getPathname();

			if ($this->limit > 0 && count($files) >= $this->limit) {
				$this->truncated = true;

				break;
			}
		}

		return $files;
	}

	/**
	 * Normalises a path by eliminating `.` and `..` segments.
	 *
	 * This method does not touch the filesystem; it purely manipulates the input
	 * string. It is not used as the primary resolution mechanism (realpath() is
	 * preferred when the path exists), but serves as a fallback for non-existent
	 * paths to prevent directory-traversal attacks before file creation.
	 */
	private static function normalisePath(string $path): string
	{
		$parts = explode('/', $path);
		$stack = [];

		foreach ($parts as $part) {
			if ('.' === $part || '' === $part) {
				continue;
			}

			if ('..' === $part) {
				if ([] !== $stack && '..' !== end($stack)) {
					array_pop($stack);
				} else {
					$stack[] = $part;
				}
			} else {
				$stack[] = $part;
			}
		}

		$normalised = implode('/', $stack);

		return str_starts_with($path, '/') ? '/' . $normalised : $normalised;
	}

	/**
	 * Resolves $path to an absolute path within the project root.
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

		return !$rebuilt ? '/' : $rebuilt;
	}

	/**
	 * Returns true when $path should be excluded from search results based on
	 * the SKIP_DIRS list (vendor, node_modules, .git, storage, .idea).
	 */
	private static function shouldSkipPath(string $path): bool
	{
		foreach (self::SKIP_DIRS as $dir) {
			if (str_contains($path, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Converts an absolute path to a relative path from the project root.
	 */
	private static function relativePath(string $absolutePath): string
	{
		$root = rtrim((string) Path::getRootDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if (str_starts_with($absolutePath, $root)) {
			return substr($absolutePath, strlen($root));
		}

		return $absolutePath;
	}

	/**
	 * Returns true when $file should be excluded from results based on the active
	 * filters (dot-files, gitignore, and glob pattern).
	 */
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

		if ($this->ignoreVCS && in_array($name, self::VCS_SYSTEMS, true)) {
			return false;
		}

		if ($this->ignoreDotFiles && str_starts_with($name, '.')) {
			return false;
		}

		return true;
	}
}
