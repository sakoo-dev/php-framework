<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Sakoo\Framework\Core\Clock\Clock;
use Sakoo\Framework\Core\Testing\Traits\FileAssertions;
use Sakoo\Framework\Core\Testing\Traits\HelperAssertions;

/**
 * Base test case for all Sakoo framework and application tests.
 *
 * Extends PHPUnit's TestCase and adds:
 *
 * - Kernel initialisation — the kernel is booted once per test class via
 *   setUpBeforeClass(). Subsequent classes reuse the already-initialised kernel
 *   because the $initialized flag is static. Concrete subclasses must implement
 *   NeedsKernel::runKernel() to configure and start the kernel for their context.
 *
 * - Clock reset — tearDown() resets Clock::setTestNow() to 'now' after every test
 *   so pinned test times never leak between test methods.
 *
 * - FileAssertions trait — adds assertIsExecutable() and assertIsNotExecutable().
 * - HelperAssertions trait — adds throwsException() for fluent exception assertion.
 *
 * Subclasses should not override setUpBeforeClass() without calling parent::, as
 * doing so would prevent kernel initialisation and cause all container resolutions
 * to fail.
 */
abstract class TestCase extends PHPUnitTestCase implements NeedsKernel
{
	use FileAssertions;
	use HelperAssertions;

	private static bool $initialized = false;

	/**
	 * Boots the kernel once for the entire test class. Subsequent calls within the
	 * same process are no-ops because $initialized guards against double-boot.
	 * Logs a readiness message after successful initialisation.
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		if (!self::$initialized) {
			static::runKernel();
			self::$initialized = true;

			logger()->info('Automated Tests are ready to Run!');
		}
	}

	/**
	 * Resets the Clock to real-time mode after every test method, ensuring that
	 * Clock::setTestNow() pins set inside one test cannot affect any subsequent test.
	 */
	protected function tearDown(): void
	{
		parent::tearDown();
		Clock::setTestNow();
	}
}
