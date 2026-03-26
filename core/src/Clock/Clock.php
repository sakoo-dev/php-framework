<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Clock;

use Psr\Clock\ClockInterface;
use Sakoo\Framework\Core\Clock\Exceptions\ClockTestModeException;

/**
 * PSR-20 ClockInterface implementation with test-time override support.
 *
 * In production and console modes, now() always returns the real current instant
 * as an immutable DateTimeImmutable. In test mode, the static setTestNow() method
 * allows tests to pin the clock to any date-time string accepted by the
 * DateTimeImmutable constructor, making time-dependent logic fully deterministic
 * without monkey-patching or mocking.
 *
 * The test-time override is stored as a static string so a single call to
 * setTestNow() affects all Clock instances resolved from the container during the
 * same test run. Resetting to 'now' (the default) restores real-time behaviour.
 *
 * Clock should always be injected via the ClockInterface PSR-20 contract rather
 * than instantiated directly, ensuring the concrete implementation can be swapped
 * or decorated without changing call sites.
 */
class Clock implements ClockInterface
{
	private static string $testNow = 'now';

	/**
	 * Pins the clock to a specific date-time string for the duration of a test.
	 * Calling setTestNow('now') resets it back to real-time behaviour.
	 *
	 * Only callable when the kernel is running in test mode; throws otherwise to
	 * prevent accidental time manipulation in non-test environments.
	 *
	 * @throws ClockTestModeException|\Throwable
	 */
	public static function setTestNow(string $datetime = 'now'): void
	{
		throwUnless(kernel()->isInTestMode(), new ClockTestModeException());
		self::$testNow = $datetime;
	}

	/**
	 * Returns the current instant as a DateTimeImmutable.
	 *
	 * In normal operation this is the real system time. When setTestNow() has been
	 * called in a test, the pinned date-time string is used instead.
	 *
	 * @throws \Exception
	 */
	public function now(): \DateTimeImmutable
	{
		return new \DateTimeImmutable(self::$testNow);
	}
}
