<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Path\Path;

/**
 * Extends PHP's SplFileObject with framework-aware namespace resolution.
 *
 * Provides two additional capabilities on top of the standard SplFileObject API:
 *
 * - getNamespace() derives the fully-qualified PSR-4 class name for this file by
 *   stripping the core directory prefix and converting the remaining relative path
 *   to a namespace string via Path::pathToNamespace().
 *
 * - isClassFile() checks whether the resolved namespace actually corresponds to a
 *   loaded class, allowing callers to distinguish PHP files that define a class
 *   from plain scripts, configuration files, or helpers.
 */
class SplFileObject extends \SplFileObject
{
	/**
	 * Returns true when the namespace derived from this file's path corresponds to
	 * an existing, autoloaded class.
	 */
	public function isClassFile(): bool
	{
		return class_exists($this->getNamespace());
	}

	/**
	 * Derives the fully-qualified framework class name for this file.
	 *
	 * Computes the path relative to the core source root by stripping the core
	 * directory prefix, prepends 'src', and delegates to Path::pathToNamespace()
	 * for the final conversion.
	 *
	 * @return class-string
	 */
	public function getNamespace(): string
	{
		$relativePath = 'src' . str_replace(Path::getCoreDir() ?: '', '', $this->getRealPath());

		return Path::pathToNamespace($relativePath);
	}
}
