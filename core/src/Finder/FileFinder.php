<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Assert\Assert;

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

	private const VCS_SYSTEMS = ['.git', '.svn', '.hg', '.bzr'];

	public function __construct(private readonly string $path) {}

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
			}
		}

		return $files;
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
