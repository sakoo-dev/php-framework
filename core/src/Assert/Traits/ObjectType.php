<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides object-type assertion methods for the Assert class.
 *
 * Covers is_object detection and instanceof / subclass-of checks.
 */
trait ObjectType
{
	/**
	 * Asserts that $value is an object.
	 */
	public static function object(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not an object', Str::fromType($value));
		static::throwUnless(is_object($value), $message);
	}

	/**
	 * Asserts that $value is NOT an object.
	 */
	public static function notObject(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is an object', Str::fromType($value));
		static::throwIf(is_object($value), $message);
	}

	/**
	 * Asserts that $value is an instance of or a subclass of $class.
	 * Accepts both objects and class-name strings.
	 */
	public static function instanceOf(mixed $value, string $class, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not instance of %s', Str::fromType($value), $class);
		static::throwUnless((is_string($value) || is_object($value)) && is_subclass_of($value, $class), $message);
	}

	/**
	 * Asserts that $value is NOT an instance of or subclass of $class.
	 */
	public static function notInstanceOf(mixed $value, string $class, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is instance of %s', Str::fromType($value), $class);
		static::throwIf((is_string($value) || is_object($value)) && is_subclass_of($value, $class), $message);
	}
}
