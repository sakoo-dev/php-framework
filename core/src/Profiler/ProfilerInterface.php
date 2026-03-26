<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Profiler;

/**
 * Contract for measuring elapsed time between named checkpoints.
 *
 * A profiler records a start timestamp for a named key and later computes the
 * number of milliseconds elapsed since that key was started. Keys are arbitrary
 * strings, allowing multiple independent timings to run concurrently within the
 * same request lifecycle.
 */
interface ProfilerInterface
{
	/**
	 * Records the current time as the start of the measurement identified by $key.
	 * Calling start() again with the same $key resets the start time for that key.
	 */
	public function start(string $key): void;

	/**
	 * Returns the number of milliseconds elapsed since start() was called for $key.
	 */
	public function elapsedTime(string $key): int;
}
