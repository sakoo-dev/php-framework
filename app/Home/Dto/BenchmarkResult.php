<?php

declare(strict_types=1);

namespace App\Home\Dto;

/**
 * Single benchmark run result parsed from Apache Benchmark (ab) output.
 *
 * Immutable value object holding the key performance metrics from one ab run.
 * Serialisable to/from JSON for time-series persistence.
 */
final readonly class BenchmarkResult
{
	public function __construct(
		public string $timestamp,
		public string $target,
		public int $concurrency,
		public int $totalRequests,
		public int $completedRequests,
		public int $failedRequests,
		public float $totalTimeSec,
		public float $requestsPerSec,
		public float $meanLatencyMs,
		public float $p50Ms,
		public float $p90Ms,
		public float $p95Ms,
		public float $p99Ms,
		public float $longestMs,
		public string $sapi,
	) {}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'timestamp' => $this->timestamp,
			'target' => $this->target,
			'concurrency' => $this->concurrency,
			'total_requests' => $this->totalRequests,
			'completed' => $this->completedRequests,
			'failed' => $this->failedRequests,
			'total_time_sec' => $this->totalTimeSec,
			'rps' => $this->requestsPerSec,
			'mean_ms' => $this->meanLatencyMs,
			'p50_ms' => $this->p50Ms,
			'p90_ms' => $this->p90Ms,
			'p95_ms' => $this->p95Ms,
			'p99_ms' => $this->p99Ms,
			'longest_ms' => $this->longestMs,
			'sapi' => $this->sapi,
		];
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromArray(array $data): self
	{
		$str = static fn (string $key): string => isset($data[$key]) && is_scalar($data[$key]) ? (string) $data[$key] : '';
		$int = static fn (string $key): int => isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : 0;
		$float = static fn (string $key): float => isset($data[$key]) && is_numeric($data[$key]) ? (float) $data[$key] : 0.0;

		return new self(
			timestamp: $str('timestamp'),
			target: $str('target'),
			concurrency: $int('concurrency'),
			totalRequests: $int('total_requests'),
			completedRequests: $int('completed'),
			failedRequests: $int('failed'),
			totalTimeSec: $float('total_time_sec'),
			requestsPerSec: $float('rps'),
			meanLatencyMs: $float('mean_ms'),
			p50Ms: $float('p50_ms'),
			p90Ms: $float('p90_ms'),
			p95Ms: $float('p95_ms'),
			p99Ms: $float('p99_ms'),
			longestMs: $float('longest_ms'),
			sapi: $str('sapi'),
		);
	}
}
