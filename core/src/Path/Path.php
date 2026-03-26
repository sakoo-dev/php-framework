<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Path;

use Sakoo\Framework\Core\Finder\FileFinder;
use Sakoo\Framework\Core\Finder\SplFileObject;

/**
 * Centralised registry of well-known filesystem paths within a Sakoo project.
 *
 * All path resolution is static so the class acts as a pure namespace rather than
 * a service, avoiding the need to inject it throughout the codebase. Paths are
 * derived at runtime from the current working directory (getRootDir) or from the
 * location of this file itself (getCoreDir), ensuring they are correct regardless
 * of where the process was started from.
 *
 * Utility methods convert between PSR-4 namespace strings and filesystem paths so
 * the framework can locate, load, and introspect its own source files without
 * maintaining a separate manifest.
 */
class Path
{
	/**
	 * Returns the project root directory, which is the current working directory
	 * of the running PHP process. Returns false when getcwd() fails.
	 */
	public static function getRootDir(): false|string
	{
		return getcwd();
	}

	/**
	 * Returns the absolute path to the framework core source directory (one level
	 * above this file). Returns false when realpath() cannot resolve the path.
	 */
	public static function getCoreDir(): false|string
	{
		return realpath(__DIR__ . '/../');
	}

	/**
	 * Returns the absolute path to the project's vendor directory.
	 */
	public static function getVendorDir(): string
	{
		return static::getRootDir() . '/vendor';
	}

	/**
	 * Returns the absolute path to the project's storage directory.
	 */
	public static function getStorageDir(): string
	{
		return static::getRootDir() . '/storage';
	}

	/**
	 * Returns the directory where log files are written.
	 *
	 * In test mode the temporary test directory is used to avoid polluting the
	 * real storage tree. In all other modes, logs are written under storage/logs.
	 */
	public static function getLogsDir(): string
	{
		if (kernel()->isInTestMode()) {
			return Path::getTempTestDir() . '/logs';
		}

		return static::getStorageDir() . '/logs';
	}

	/**
	 * Returns the temporary directory used exclusively during test runs.
	 * Isolating test artefacts here prevents leftover files from affecting
	 * production storage between runs.
	 */
	public static function getTempTestDir(): string
	{
		return '/tmp/sakoo-test';
	}

	/**
	 * Returns all PHP files found recursively under the project root directory,
	 * excluding VCS directories, VCS-ignored paths, and dot-files.
	 *
	 * @return SplFileObject[]
	 */
	public static function getProjectPHPFiles(): array
	{
		if ($dir = Path::getRootDir()) {
			return static::getPHPFilesOf($dir);
		}

		return [];
	}

	/**
	 * Returns all PHP files found recursively under the framework core directory,
	 * excluding VCS directories, VCS-ignored paths, and dot-files.
	 *
	 * @return SplFileObject[]
	 */
	public static function getCorePHPFiles(): array
	{
		if ($dir = Path::getCoreDir()) {
			return static::getPHPFilesOf($dir);
		}

		return [];
	}

	/**
	 * Returns all PHP files found recursively under $path using FileFinder,
	 * with VCS directories, VCS-ignored paths, and dot-files excluded.
	 *
	 * @return SplFileObject[]
	 */
	public static function getPHPFilesOf(string $path): array
	{
		return (new FileFinder($path))
			->pattern('*.php')
			->ignoreVCS()
			->ignoreVCSIgnored()
			->ignoreDotFiles()
			->getFiles();
	}

	/**
	 * Converts a fully-qualified framework namespace string to a relative file path.
	 *
	 * Replaces the 'Sakoo\Framework\Core' prefix with 'src' and converts namespace
	 * separators to directory separators, appending '.php'. Used for locating source
	 * files from reflection data without hitting the filesystem.
	 */
	public static function namespaceToPath(string $namespace): string
	{
		return str_replace(
			['Sakoo\Framework\Core', '\\'],
			['src', '/'],
			$namespace,
		) . '.php';
	}

	/**
	 * Converts a relative file path back to a fully-qualified framework class name.
	 *
	 * Strips the '.php' extension, replaces the 'src' prefix with 'Sakoo\Framework\Core',
	 * and converts directory separators to namespace separators.
	 *
	 * @return class-string
	 */
	public static function pathToNamespace(string $path): string
	{
		// @phpstan-ignore return.type
		return str_replace(
			['.php', 'src', '/'],
			['', 'Sakoo\Framework\Core', '\\'],
			$path,
		);
	}
}
