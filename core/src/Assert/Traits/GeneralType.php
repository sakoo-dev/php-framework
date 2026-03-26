<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides general-purpose value-comparison assertion methods for the Assert class.
 *
 * Covers string length, array/Countable count, loose equality (==), strict equality
 * (===), and emptiness. Each method generates a descriptive failure message via
 * Str::fromType() when no custom message is supplied.
 */
trait GeneralType
{
	/**
	 * Asserts that the multibyte character length of $value equals $length.
	 */
	public static function length(string $value, int $length, string $message = ''): void
	{
		$message = $message ?: sprintf(
			'The length of %s is %s, Expected %s',
			Str::fromType($value),
			strlen($value),
			$length,
		);

		static::same($length, mb_strlen($value), $message);
	}

	/**
	 * Asserts that the count of $value (array or Countable) equals $count.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	public static function count(array|\Countable $value, int $count, string $message = ''): void
	{
		$message = $message ?: sprintf(
			'The count of %s is %s, Expected %s',
			Str::fromType($value),
			count($value),
			$count,
		);

		static::same($count, count($value), $message);
	}

	/**
	 * Asserts that $value is loosely equal (==) to $expected.
	 */
	public static function equals(mixed $value, mixed $expected, string $message = ''): void
	{
		$message = $message ?: sprintf(
			'Given value %s is not equals to %s',
			Str::fromType($value),
			Str::fromType($expected),
		);

		static::throwUnless($value == $expected, $message);
	}

	/**
	 * Asserts that $value is NOT loosely equal (!=) to $expected.
	 */
	public static function notEquals(mixed $value, mixed $expected, string $message = ''): void
	{
		$message = $message ?: sprintf(
			'Given value %s is equals to %s',
			Str::fromType($value),
			Str::fromType($expected),
		);

		static::throwIf($value == $expected, $message);
	}

	/**
	 * Asserts that $value is strictly identical (===) to $expected.
	 */
	public static function same(mixed $value, mixed $expected, string $message = ''): void
	{
		$message = $message ?: sprintf(
			'Given value %s is not same to %s',
			Str::fromType($value),
			Str::fromType($expected),
		);

		static::throwUnless($value === $expected, $message);
	}

	/**
	 * Asserts that $value is NOT strictly identical (!==) to $expected.
	 */
	public static function notSame(mixed $value, mixed $expected, string $message = ''): void
	{
		$message = $message ?: sprintf(
			'Given value %s is same to %s',
			Str::fromType($value),
			Str::fromType($expected),
		);

		static::throwIf($value === $expected, $message);
	}

	/**
	 * Asserts that $value is empty as evaluated by PHP's empty() construct.
	 */
	public static function empty(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not empty', Str::fromType($value));
		static::throwUnless(empty($value), $message);
	}

	/**
	 * Asserts that $value is NOT empty.
	 */
	public static function notEmpty(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is empty', Str::fromType($value));
		static::throwIf(empty($value), $message);
	}
}
