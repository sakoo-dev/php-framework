<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides filesystem-related assertion methods for the Assert class.
 *
 * All methods accept a path string and delegate to the corresponding PHP filesystem
 * functions. Each has a positive form (assert the condition holds) and a negative
 * form (assert the condition does not hold).
 */
trait FileType
{
	/**
	 * Asserts that $value is an existing directory path.
	 */
	public static function dir(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a directory', Str::fromType($value));
		static::throwUnless(is_dir($value), $message);
	}

	/**
	 * Asserts that $value is NOT a directory path.
	 */
	public static function notDir(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a directory', Str::fromType($value));
		static::throwIf(is_dir($value), $message);
	}

	/**
	 * Asserts that $value is an existing regular file.
	 */
	public static function file(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a file', Str::fromType($value));
		static::throwUnless(is_file($value), $message);
	}

	/**
	 * Asserts that $value is NOT a regular file.
	 */
	public static function notFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a file', Str::fromType($value));
		static::throwIf(is_file($value), $message);
	}

	/**
	 * Asserts that $value is a symbolic link.
	 */
	public static function link(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a link', Str::fromType($value));
		static::throwUnless(is_link($value), $message);
	}

	/**
	 * Asserts that $value is NOT a symbolic link.
	 */
	public static function notLink(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a link', Str::fromType($value));
		static::throwIf(is_link($value), $message);
	}

	/**
	 * Asserts that $value was uploaded via HTTP POST (is_uploaded_file).
	 */
	public static function uploadedFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a uploaded file', Str::fromType($value));
		static::throwUnless(is_uploaded_file($value), $message);
	}

	/**
	 * Asserts that $value was NOT uploaded via HTTP POST.
	 */
	public static function notUploadedFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a uploaded file', Str::fromType($value));
		static::throwIf(is_uploaded_file($value), $message);
	}

	/**
	 * Asserts that $value is an executable file path.
	 */
	public static function executableFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a executable file', Str::fromType($value));
		static::throwUnless(is_executable($value), $message);
	}

	/**
	 * Asserts that $value is NOT an executable file path.
	 */
	public static function notExecutableFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a executable file', Str::fromType($value));
		static::throwIf(is_executable($value), $message);
	}

	/**
	 * Asserts that $value is a writable file or directory path.
	 */
	public static function writableFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a writable file', Str::fromType($value));
		static::throwUnless(is_writable($value), $message);
	}

	/**
	 * Asserts that $value is NOT a writable path.
	 */
	public static function notWritableFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a writable file', Str::fromType($value));
		static::throwIf(is_writable($value), $message);
	}

	/**
	 * Asserts that $value is a readable file or directory path.
	 */
	public static function readableFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a readable file', Str::fromType($value));
		static::throwUnless(is_readable($value), $message);
	}

	/**
	 * Asserts that $value is NOT a readable path.
	 */
	public static function notReadableFile(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a readable file', Str::fromType($value));
		static::throwIf(is_readable($value), $message);
	}

	/**
	 * Asserts that $value points to an existing file or directory (file_exists).
	 */
	public static function exists(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not an exists file', Str::fromType($value));
		static::throwUnless(file_exists($value), $message);
	}

	/**
	 * Asserts that $value does NOT point to any existing filesystem entry.
	 */
	public static function notExists(string $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is an exists file', Str::fromType($value));
		static::throwIf(file_exists($value), $message);
	}
}
