<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Profiler;

use Psr\Clock\ClockInterface;

/**
 * Millisecond-precision profiler with request concurrency tracking.
 *
 * Timestamps are captured in Unix milliseconds via ClockInterface for
 * deterministic test control. High-resolution timing uses hrtime(true)
 * for sub-millisecond precision without DateTimeImmutable allocation.
 *
 * The concurrency counter tracks active/peak/total requests at the process
 * level. Under Swoole's single-threaded cooperative model, a simple int
 * is safe — coroutine switches only happen at yield points, never mid-opcode.
 */
class Profiler implements ProfilerInterface
{
	/** @var int[] */
	protected array $instances = [];

	private int $activeRequests = 0;
	private int $peakRequests = 0;
	private int $totalRequests = 0;

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

	/**
	 * Returns nanosecond-precision monotonic timestamp via hrtime.
	 */
	public function hrtimeNs(): int
	{
		return hrtime(true);
	}

	/**
	 * Increments the active request counter and updates peak.
	 */
	public function requestStarted(): void
	{
		++$this->activeRequests;
		++$this->totalRequests;

		if ($this->activeRequests > $this->peakRequests) {
			$this->peakRequests = $this->activeRequests;
		}
	}

	/**
	 * Decrements the active request counter.
	 */
	public function requestFinished(): void
	{
		--$this->activeRequests;
	}

	public function activeRequests(): int
	{
		return $this->activeRequests;
	}

	public function peakRequests(): int
	{
		return $this->peakRequests;
	}

	public function totalRequests(): int
	{
		return $this->totalRequests;
	}
}
