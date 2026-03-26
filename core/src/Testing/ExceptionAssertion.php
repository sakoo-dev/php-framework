<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Testing;

use PHPUnit\Framework\TestCase;

/**
 * Fluent builder for asserting that a callable raises an exception.
 *
 * Returned by HelperAssertions::throwsException(), this class provides an
 * ergonomic alternative to PHPUnit's expectException() family of methods,
 * allowing the expected type, message, and code to be specified in a readable
 * chain before the assertion is executed:
 *
 *   $this->throwsException(fn() => $obj->doSomething())
 *        ->withType(DomainException::class)
 *        ->withMessage('Invalid state')
 *        ->withCode(42)
 *        ->validate();
 *
 * validate() invokes the callable, catches any \Exception, and asserts each of the
 * configured properties against the caught exception. If no exception is thrown,
 * the test fails with "Error does not raised!". The type, message, and code
 * constraints are all optional — omit any of them to skip that assertion.
 */
class ExceptionAssertion
{
	private ?int $code = null;
	private ?string $type = null;
	private ?string $message = null;

	/**
	 * @param callable $fn the callable expected to throw an exception
	 */
	public function __construct(private readonly TestCase $phpunit, private $fn) {}

	/**
	 * Constrains the assertion to verify that the exception's code equals $code.
	 */
	public function withCode(int $code): static
	{
		$this->code = $code;

		return $this;
	}

	/**
	 * Constrains the assertion to verify that the exception is an instance of $type.
	 */
	public function withType(string $type): static
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * Constrains the assertion to verify that the exception message equals $message.
	 */
	public function withMessage(string $message): static
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * Executes the callable and asserts that it throws an exception satisfying all
	 * configured constraints. Fails the test immediately when no exception is raised.
	 */
	public function validate(): void
	{
		$raised = false;

		try {
			call_user_func($this->fn);
		} catch (\Exception $exception) {
			$raised = true;

			if (!is_null($this->type)) {
				$this->phpunit::assertTrue(is_a($exception, $this->type));
			}

			if (!is_null($this->message)) {
				$this->phpunit::assertEquals($this->message, $exception->getMessage());
			}

			if (!is_null($this->code)) {
				$this->phpunit::assertEquals($this->code, $exception->getCode());
			}
		} finally {
			$raised ?: $this->phpunit::fail('Error does not raised!');
		}
	}
}
