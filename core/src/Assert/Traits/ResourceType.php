<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Traits;

use Sakoo\Framework\Core\Str\Str;

/**
 * Provides resource-type assertion methods for the Assert class.
 */
trait ResourceType
{
	/**
	 * Asserts that $value is a PHP resource (e.g. a file handle or stream).
	 */
	public static function resource(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is not a resource', Str::fromType($value));
		static::throwUnless(is_resource($value), $message);
	}

	/**
	 * Asserts that $value is NOT a PHP resource.
	 */
	public static function notResource(mixed $value, string $message = ''): void
	{
		$message = $message ?: sprintf('Given value %s is a resource', Str::fromType($value));
		static::throwIf(is_resource($value), $message);
	}
}
