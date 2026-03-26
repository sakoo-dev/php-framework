<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides scalar-type assertion methods for the Assert class.
 *
 * A scalar value in PHP is one of: integer, float, string, or boolean.
 */
trait ScalarType
{
	/**
	 * Asserts that $value is a scalar (int, float, string, or bool).
	 */
	public static function scalar(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not scalar', Str::fromType($value));
		static::throwUnless(is_scalar($value), $message);
	}

	/**
	 * Asserts that $value is NOT scalar.
	 */
	public static function notScalar(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is scalar', Str::fromType($value));
		static::throwIf(is_scalar($value), $message);
	}
}
