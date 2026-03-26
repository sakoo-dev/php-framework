<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides traversable and collection-type assertion methods for the Assert class.
 *
 * Covers array, Countable, and iterable type checks.
 */
trait TraversableType
{
	/**
	 * Asserts that $value is a PHP array.
	 */
	public static function array(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not an array', Str::fromType($value));
		static::throwUnless(is_array($value), $message);
	}

	/**
	 * Asserts that $value is NOT a PHP array.
	 */
	public static function notArray(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is an array', Str::fromType($value));
		static::throwIf(is_array($value), $message);
	}

	/**
	 * Asserts that $value is countable (an array or an object implementing Countable).
	 */
	public static function countable(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not countable', Str::fromType($value));
		static::throwUnless(is_countable($value), $message);
	}

	/**
	 * Asserts that $value is NOT countable.
	 */
	public static function notCountable(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is countable', Str::fromType($value));
		static::throwIf(is_countable($value), $message);
	}

	/**
	 * Asserts that $value is iterable (an array or an object implementing Traversable).
	 */
	public static function iterable(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not iterable', Str::fromType($value));
		static::throwUnless(is_iterable($value), $message);
	}

	/**
	 * Asserts that $value is NOT iterable.
	 */
	public static function notIterable(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is iterable', Str::fromType($value));
		static::throwIf(is_iterable($value), $message);
	}
}
