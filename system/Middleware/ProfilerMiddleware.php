<?php

declare(strict_types=1);

namespace System\Middleware;

use Sakoo\Framework\Core\Constants;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Middleware\Middleware;
use Sakoo\Framework\Core\Profiler\ProfilerInterface;

/**
 * Measures per-request performance and attaches diagnostic response headers.
 *
 * Uses the Kernel Profiler for timing and concurrency tracking — all state
 * lives in the singleton Profiler, safe under Swoole's cooperative model.
 *
 * Response headers emitted:
 *
 * - X-Response-Time-Ms      — wall-clock request time (hrtime, monotonic)
 * - X-Response-Time-Us       — same in microseconds for sub-ms precision
 * - X-Process-Memory-Kb      — current process memory
 * - X-Process-Memory-Peak-Kb — process peak memory watermark
 * - X-Concurrent-Requests    — active requests in this worker right now
 * - X-Concurrent-Peak        — peak concurrency seen in this worker's lifetime
 * - X-Total-Requests         — total requests this worker has handled
 * - X-Runtime-Sapi           — actual PHP SAPI
 * - X-Worker-Pid             — worker process ID for correlation
 * - X-Powered-By             — framework identity for Wappalyzer detection
 */
class ProfilerMiddleware extends Middleware
{
	public function handle(HttpRequest $request, \Closure $next): HttpResponse
	{
		/** @var ProfilerInterface $profiler */
		$profiler = resolve(ProfilerInterface::class);

		$startNs = $profiler->hrtimeNs();
		$profiler->requestStarted();

		try {
			$response = $next($request);
		} finally {
			$profiler->requestFinished();
		}

		$elapsedNs = $profiler->hrtimeNs() - $startNs;
		$elapsedMs = $elapsedNs / 1_000_000;
		$elapsedUs = $elapsedNs / 1_000;

		return $response
			->withHeader('X-Response-Time-Ms', sprintf('%.2f', $elapsedMs))
			->withHeader('X-Response-Time-Us', sprintf('%.0f', $elapsedUs))
			->withHeader('X-Process-Memory-Kb', (string) (int) (memory_get_usage() / 1024))
			->withHeader('X-Process-Memory-Peak-Kb', (string) (int) (memory_get_peak_usage() / 1024))
			->withHeader('X-Concurrent-Requests', (string) $profiler->activeRequests())
			->withHeader('X-Concurrent-Peak', (string) $profiler->peakRequests())
			->withHeader('X-Total-Requests', (string) $profiler->totalRequests())
			->withHeader('X-Runtime-Sapi', PHP_SAPI)
			->withHeader('X-Worker-Pid', (string) getmypid())
			->withHeader('X-Powered-By', Constants::FRAMEWORK_NAME);
	}
}
