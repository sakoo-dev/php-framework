<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides string-type assertion methods for the Assert class.
 */
trait StringType
{
	/**
	 * Asserts that $value is of type string.
	 */
	public static function string(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not string', Str::fromType($value));
		static::throwUnless(is_string($value), $message);
	}

	/**
	 * Asserts that $value is NOT of type string.
	 */
	public static function notString(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is string', Str::fromType($value));
		static::throwIf(is_string($value), $message);
	}
}
