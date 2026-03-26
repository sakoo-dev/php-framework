<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Testing;

/**
 * Contract for test classes that require a running Sakoo kernel.
 *
 * Implemented by TestCase and enforced on all subclasses. runKernel() is called
 * once per test class by TestCase::setUpBeforeClass() before any test method runs.
 * Concrete implementations should construct the Kernel with the appropriate Mode
 * and Environment, register ServiceLoaders, and call Kernel::run() so the container
 * is fully populated and all global helpers are available during tests.
 */
interface NeedsKernel
{
	/**
	 * Bootstraps and starts the Sakoo kernel for the test suite.
	 * Called once per test class before any test methods execute.
	 */
	public static function runKernel(): void;
}
