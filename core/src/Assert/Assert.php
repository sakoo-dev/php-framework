<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert;

use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\Assert\Traits\BooleanType;
use Sakoo\Framework\Core\Assert\Traits\CallableType;
use Sakoo\Framework\Core\Assert\Traits\FileType;
use Sakoo\Framework\Core\Assert\Traits\GeneralType;
use Sakoo\Framework\Core\Assert\Traits\NullType;
use Sakoo\Framework\Core\Assert\Traits\NumberType;
use Sakoo\Framework\Core\Assert\Traits\ObjectType;
use Sakoo\Framework\Core\Assert\Traits\ResourceType;
use Sakoo\Framework\Core\Assert\Traits\ScalarType;
use Sakoo\Framework\Core\Assert\Traits\StringType;
use Sakoo\Framework\Core\Assert\Traits\TraversableType;

/**
 * Static assertion library for validating values at runtime.
 *
 * Aggregates all type-specific assertion traits (boolean, callable, file, general,
 * null, number, object, resource, scalar, string, traversable) into a single
 * entry point. Every static method validates one condition and throws
 * InvalidArgumentException immediately when the assertion fails — making Assert
 * suitable for guard clauses at the top of methods and constructors.
 *
 * For scenarios where multiple assertions must all be evaluated before any failure
 * is reported, use Assert::lazy() to obtain a LazyAssertion instance that collects
 * all failures and throws a single LazyAssertionException at the end.
 *
 * For fluent, chainable single-value assertions, use Assert::that($value) to obtain
 * an AssertionChain that proxies every Assert method with the bound value already
 * supplied as the first argument.
 *
 * The protected throwIf() and throwUnless() helpers are the sole throw sites used
 * by all traits, keeping error-throwing logic in one place.
 */
class Assert
{
	use BooleanType;
	use CallableType;
	use FileType;
	use GeneralType;
	use NullType;
	use NumberType;
	use ObjectType;
	use ResourceType;
	use ScalarType;
	use StringType;
	use TraversableType;

	/**
	 * Returns an AssertionChain bound to $value, enabling fluent chained assertions
	 * without repeating the value on every call.
	 */
	public static function that(mixed $value): AssertionChain
	{
		return new AssertionChain($value);
	}

	/**
	 * Returns a LazyAssertion instance that accumulates all assertion failures and
	 * reports them together via LazyAssertionException when validate() is called.
	 */
	public static function lazy(): LazyAssertion
	{
		return new LazyAssertion();
	}

	/**
	 * Throws InvalidArgumentException when $condition is true.
	 * Used internally by all assertion traits as the single throw site for
	 * "must not be" style assertions.
	 *
	 * @throws InvalidArgumentException
	 */
	protected static function throwIf(bool $condition, string $message = ''): void
	{
		static::throwUnless(!$condition, $message);
	}

	/**
	 * Throws InvalidArgumentException when $condition is false.
	 * Used internally by all assertion traits as the single throw site for
	 * "must be" style assertions.
	 *
	 * @throws InvalidArgumentException
	 */
	protected static function throwUnless(bool $condition, string $message = ''): void
	{
		$condition ?: throw new InvalidArgumentException($message);
	}
}
