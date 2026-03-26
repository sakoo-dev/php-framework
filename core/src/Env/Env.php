<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Env;

use Sakoo\Framework\Core\FileSystem\Storage;

/**
 * Reads and exposes environment variables, with optional loading from a .env file.
 *
 * get() provides a typed, null-safe wrapper around getenv() with a configurable
 * fallback so callers never need to guard against false returns.
 *
 * load() parses a simple KEY=VALUE .env file (one assignment per line, no sections,
 * no quoted values, no inline comments) and populates both the process environment
 * via putenv() and the $_ENV superglobal so the values are accessible through
 * standard PHP mechanisms as well as through get(). Lines that do not match the
 * expected pattern are silently skipped, allowing blank lines and comments to be
 * present in the file without causing parse errors.
 */
class Env
{
	/**
	 * Returns the value of the environment variable identified by $key, or $default
	 * when the variable is not set or is an empty string.
	 */
	public static function get(string $key, mixed $default = null): mixed
	{
		return getenv($key) ?: $default;
	}

	/**
	 * Parses a KEY=VALUE .env file and registers each valid assignment into both
	 * the process environment (putenv) and the $_ENV superglobal.
	 *
	 * When the file does not exist, the method returns without side effects. Each
	 * line is trimmed before being tested against the KEY=VALUE pattern; lines that
	 * do not match (blank lines, comment lines, malformed entries) are ignored.
	 */
	public static function load(Storage $file): void
	{
		/** @var array<string> $lines */
		$lines = $file->exists() ? $file->readLines() : [];

		foreach ($lines as $line) {
			$line = trim($line);

			if (self::matchesPattern($line)) {
				self::storeValue(...self::getKeyValue($line));
			}
		}
	}

	/**
	 * Returns true when $line conforms to the KEY=VALUE pattern where KEY starts
	 * with a letter or underscore and contains only alphanumeric characters and
	 * underscores.
	 */
	private static function matchesPattern(string $line): bool
	{
		return (bool) preg_match('/^([a-zA-Z_]+[a-zA-Z0-9_]*)=(.*)$/', $line);
	}

	/**
	 * Splits a KEY=VALUE line into a two-element list [key, value], splitting on
	 * the first '=' only so values may contain '=' characters.
	 *
	 * @return list<string>
	 */
	private static function getKeyValue(string $line): array
	{
		return explode('=', $line, 2);
	}

	/**
	 * Registers $key=$value in the process environment via putenv() and in $_ENV
	 * so the value is reachable through all standard PHP environment accessors.
	 */
	private static function storeValue(string $key, string $value): void
	{
		putenv("$key=$value");
		$_ENV[$key] = $value;
	}
}
