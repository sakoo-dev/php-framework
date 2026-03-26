<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides boolean-type assertion methods for the Assert class.
 *
 * Each method throws InvalidArgumentException with a descriptive message when the
 * assertion fails. The message includes a human-readable representation of the
 * offending value produced by Str::fromType().
 */
trait BooleanType
{
	/**
	 * Asserts that $value is strictly identical to true.
	 */
	public static function true(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not true', Str::fromType($value));
		static::throwUnless(true === $value, $message);
	}

	/**
	 * Asserts that $value is strictly identical to false.
	 */
	public static function false(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not false', Str::fromType($value));
		static::throwUnless(false === $value, $message);
	}

	/**
	 * Asserts that $value is of type bool (true or false).
	 */
	public static function bool(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not boolean', Str::fromType($value));
		static::throwUnless(is_bool($value), $message);
	}

	/**
	 * Asserts that $value is NOT of type bool.
	 */
	public static function notBool(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is boolean', Str::fromType($value));
		static::throwIf(is_bool($value), $message);
	}
}
