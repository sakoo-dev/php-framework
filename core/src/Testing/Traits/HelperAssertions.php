<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Testing\Traits;

use Sakoo\Framework\Core\Testing\ExceptionAssertion;

/**
 * Provides the throwsException() fluent assertion helper for TestCase subclasses.
 *
 * Mixed into TestCase, this trait adds a single protected method that wraps a
 * callable in an ExceptionAssertion builder, allowing tests to assert exception
 * type, message, and code in a readable, chainable style.
 */
trait HelperAssertions
{
	/**
	 * Returns an ExceptionAssertion bound to $fn. Chain withType(), withMessage(),
	 * and/or withCode() to configure the expected exception properties, then call
	 * validate() to execute the callable and run the assertions.
	 */
	protected function throwsException(callable $fn): ExceptionAssertion
	{
		return new ExceptionAssertion($this, $fn);
	}
}
