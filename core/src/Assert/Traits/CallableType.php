<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides callable-type assertion methods for the Assert class.
 */
trait CallableType
{
	/**
	 * Asserts that $value is callable (a closure, invokable object, or valid callback).
	 */
	public static function callable(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not callable', Str::fromType($value));
		static::throwUnless(is_callable($value), $message);
	}

	/**
	 * Asserts that $value is NOT callable.
	 */
	public static function notCallable(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is callable', Str::fromType($value));
		static::throwIf(is_callable($value), $message);
	}
}
