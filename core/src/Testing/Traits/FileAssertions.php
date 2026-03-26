<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Testing\Traits;

/**
 * Provides filesystem-executability assertion helpers for TestCase subclasses.
 *
 * Mixed into TestCase, this trait supplements PHPUnit's built-in file assertions
 * with two methods that check whether a file is executable, which PHPUnit does
 * not provide out of the box. Used primarily to verify that generated or deployed
 * files carry the correct permission bits.
 */
trait FileAssertions
{
	/**
	 * Asserts that the file at $filename is executable by the current process.
	 * Fails with $message (or a generated message) when is_executable() returns false.
	 */
	protected static function assertIsExecutable(string $filename, string $message = ''): void
	{
		$message = $message ?: "Failed asserting that $filename is executable";
		static::assertThat(is_executable($filename), static::isTrue(), $message);
	}

	/**
	 * Asserts that the file at $filename is NOT executable by the current process.
	 * Fails with $message (or a generated message) when is_executable() returns true.
	 */
	protected static function assertIsNotExecutable(string $filename, string $message = ''): void
	{
		$message = $message ?: "Failed asserting that $filename is not executable";
		static::assertThat(is_executable($filename), static::isFalse(), $message);
	}
}
