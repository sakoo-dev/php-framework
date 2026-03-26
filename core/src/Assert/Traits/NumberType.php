<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides numeric assertion methods for the Assert class.
 *
 * Covers is_numeric detection, finite/infinite float checks, integer and float
 * type checks, and ordered comparisons (greater, greaterOrEquals, lower, lowerOrEquals).
 */
trait NumberType
{
	/**
	 * Asserts that $value is numeric (an integer, float, or numeric string).
	 */
	public static function numeric(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not numeric', Str::fromType($value));
		static::throwUnless(is_numeric($value), $message);
	}

	/**
	 * Asserts that $value is NOT numeric.
	 */
	public static function notNumeric(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is numeric', Str::fromType($value));
		static::throwIf(is_numeric($value), $message);
	}

	/**
	 * Asserts that $value is a finite float (not INF, -INF, or NAN).
	 */
	public static function finite(float $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is an infinite number', Str::fromType($value));
		static::throwUnless(is_finite($value), $message);
	}

	/**
	 * Asserts that $value is an infinite float (INF or -INF).
	 */
	public static function infinite(float $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a finite number', Str::fromType($value));
		static::throwUnless(is_infinite($value), $message);
	}

	/**
	 * Asserts that $value is of type float.
	 */
	public static function float(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a float number', Str::fromType($value));
		static::throwUnless(is_float($value), $message);
	}

	/**
	 * Asserts that $value is NOT of type float.
	 */
	public static function notFloat(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a float number', Str::fromType($value));
		static::throwIf(is_float($value), $message);
	}

	/**
	 * Asserts that $value is of type int.
	 */
	public static function int(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not an integer number', Str::fromType($value));
		static::throwUnless(is_int($value), $message);
	}

	/**
	 * Asserts that $value is NOT of type int.
	 */
	public static function notInt(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is an integer number', Str::fromType($value));
		static::throwIf(is_int($value), $message);
	}

	/**
	 * Asserts that $value is strictly greater than $expected.
	 */
	public static function greater(int $value, int $expected, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not greater than %s', $value, $expected);
		static::throwUnless($value > $expected, $message);
	}

	/**
	 * Asserts that $value is greater than or equal to $expected.
	 */
	public static function greaterOrEquals(int $value, int $expected, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not greater or equals to %s', $value, $expected);
		static::throwUnless($value >= $expected, $message);
	}

	/**
	 * Asserts that $value is strictly less than $expected.
	 */
	public static function lower(int $value, int $expected, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not lower than %s', $value, $expected);
		static::throwUnless($value < $expected, $message);
	}

	/**
	 * Asserts that $value is less than or equal to $expected.
	 */
	public static function lowerOrEquals(int $value, int $expected, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not lower or equals to %s', $value, $expected);
		static::throwUnless($value <= $expected, $message);
	}
}
