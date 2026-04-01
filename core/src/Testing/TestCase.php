<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Sakoo\Framework\Core\Clock\Clock;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Testing\Traits\FileAssertions;
use Sakoo\Framework\Core\Testing\Traits\HelperAssertions;

/**
 * Base test case for all Sakoo framework and application tests.
 *
 * Extends PHPUnit's TestCase and adds:
 *
 * - Kernel lifecycle management — the kernel is destroyed and re-booted fresh
 *   for each test class via setUpBeforeClass() and tearDownAfterClass(). This
 *   guarantees full isolation between test suites: a corrupted container, stale
 *   singleton, or leaked service loader in one test class cannot affect any
 *   subsequent class. Concrete subclasses must implement NeedsKernel::runKernel()
 *   to configure and start the kernel for their context.
 *
 * - Clock reset — tearDown() resets Clock::setTestNow() to 'now' after every test
 *   so pinned test times never leak between test methods.
 *
 * - FileAssertions trait — adds assertIsExecutable() and assertIsNotExecutable().
 * - HelperAssertions trait — adds throwsException() for fluent exception assertion.
 *
 * Subclasses should not override setUpBeforeClass() or tearDownAfterClass() without
 * calling parent::, as doing so would break kernel lifecycle management.
 */
abstract class TestCase extends PHPUnitTestCase implements NeedsKernel
{
	use FileAssertions;
	use HelperAssertions;

	/**
	 * Destroys any leftover kernel from a previous test class, then boots a
	 * fresh kernel for this class via the NeedsKernel::runKernel() contract.
	 *
	 * This ensures every test class starts with a clean kernel regardless of
	 * whether the previous class tore down correctly, and regardless of test
	 * execution order.
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		Kernel::destroy();
		static::runKernel();

		logger()->info('Automated Tests are ready to Run!');
	}

	/**
	 * Destroys the kernel after all tests in this class have completed,
	 * releasing the singleton and clearing the container so the next test
	 * class can boot its own kernel with independent configuration.
	 */
	public static function tearDownAfterClass(): void
	{
		Kernel::destroy();

		parent::tearDownAfterClass();
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
