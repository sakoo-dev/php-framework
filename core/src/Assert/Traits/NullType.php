<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides null-type assertion methods for the Assert class.
 */
trait NullType
{
	/**
	 * Asserts that $value is strictly null.
	 */
	public static function null(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not null', Str::fromType($value));
		static::throwUnless(is_null($value), $message);
	}

	/**
	 * Asserts that $value is NOT null.
	 */
	public static function notNull(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is null', Str::fromType($value));
		static::throwIf(is_null($value), $message);
	}
}
