<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Profiler;

use Psr\Clock\ClockInterface;

/**
 * Millisecond-precision profiler backed by a PSR-20 ClockInterface.
 *
 * Timestamps are captured in Unix milliseconds (seconds × 1000 + milliseconds)
 * by formatting the ClockInterface::now() result with the 'Uv' format string,
 * where 'U' is Unix epoch seconds and 'v' is milliseconds. This approach keeps
 * the implementation fully deterministic in tests when the clock is pinned via
 * Clock::setTestNow().
 *
 * Multiple concurrent timings can coexist by using distinct key strings. Keys are
 * not validated — callers must ensure they call start() before elapsedTime() for
 * any given key to avoid an undefined array key error.
 */
class Profiler implements ProfilerInterface
{
	/** @var int[] */
	protected array $instances = [];

	public function __construct(private readonly ClockInterface $clock) {}

	/**
	 * Records the current millisecond timestamp as the start time for $key.
	 */
	public function start(string $key): void
	{
		$this->instances[$key] = (int) $this->clock->now()->format('Uv');
	}

	/**
	 * Returns the number of milliseconds elapsed since start() was called for $key.
	 */
	public function elapsedTime(string $key): int
	{
		return (int) $this->clock->now()->format('Uv') - $this->instances[$key];
	}
}
