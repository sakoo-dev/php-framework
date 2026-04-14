<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Profiler;

/**
 * Contract for measuring elapsed time between named checkpoints and tracking
 * per-process request concurrency.
 *
 * A profiler records a start timestamp for a named key and later computes the
 * number of milliseconds elapsed since that key was started. Keys are arbitrary
 * strings, allowing multiple independent timings to run concurrently within the
 * same request lifecycle.
 *
 * The concurrency methods track active/peak/total request counts at the process
 * level. Under Swoole all coroutines share the same counter; under FPM each
 * worker handles one request at a time so active is always 0 or 1.
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

	/**
	 * Returns nanosecond-precision monotonic timestamp via hrtime.
	 * Use for sub-millisecond measurements without ClockInterface overhead.
	 */
	public function hrtimeNs(): int;

	/**
	 * Increments the active request counter. Call at request entry.
	 */
	public function requestStarted(): void;

	/**
	 * Decrements the active request counter. Call at request exit.
	 */
	public function requestFinished(): void;

	/**
	 * Returns the number of requests currently being processed in this worker.
	 */
	public function activeRequests(): int;

	/**
	 * Returns the peak concurrent request count seen in this worker's lifetime.
	 */
	public function peakRequests(): int;

	/**
	 * Returns the total number of requests this worker has handled.
	 */
	public function totalRequests(): int;
}
