<?php

declare(strict_types=1);

namespace App\Home\Controllers;

use App\Home\Dto\BenchmarkResult;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Http\Controller;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Profiler\ProfilerInterface;
use Swoole\Coroutine;
use System\Path\Path;

/**
 * Single source of truth for all runtime and benchmark metrics.
 *
 * GET /metrics — returns live runtime stats, concurrency, memory, PHP info,
 * Swoole stats (when applicable), and stored benchmark results.
 */
class MetricsController extends Controller
{
	private const string BENCHMARK_FILE = '/benchmark/results.jsonl';

	public function index(HttpRequest $request): HttpResponse
	{
		$profiler = resolve(ProfilerInterface::class);
		$kernel = Kernel::getInstance();

		return $this->json([
			'runtime' => $this->getRuntimeData($kernel),
			'concurrency' => [
				'active_requests' => $profiler->activeRequests(),
				'peak_requests' => $profiler->peakRequests(),
				'total_requests' => $profiler->totalRequests(),
			],
			'memory' => $this->getMemoryData(),
			'php' => $this->getPhpData(),
			'swoole' => $this->getSwooleData(),
			'benchmark' => $this->getBenchmarksData(),
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getSwooleData(): array
	{
		if (!('cli' === PHP_SAPI && extension_loaded('swoole'))) {
			return [];
		}

		/** @var array<string, mixed> $coStats */
		$coStats = Coroutine::stats();

		return [
			'version' => SWOOLE_VERSION,
			'coroutines_active' => $coStats['coroutine_num'] ?? 0,
			'coroutines_peak' => $coStats['coroutine_peak_num'] ?? 0,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getRuntimeData(Kernel $kernel): array
	{
		return [
			'sapi' => PHP_SAPI,
			'transport' => 'cli' === PHP_SAPI ? 'swoole' : 'fpm',
			'pid' => getmypid(),
			'mode' => $kernel->getMode()->value,
			'environment' => $kernel->isInDebugEnv() ? 'debug' : 'production',
			'replica_id' => $kernel->getReplicaId(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getMemoryData(): array
	{
		return [
			'process_current_kb' => (int) (memory_get_usage() / 1024),
			'process_peak_kb' => (int) (memory_get_peak_usage() / 1024),
			'process_real_current_kb' => (int) (memory_get_usage(true) / 1024),
			'process_real_peak_kb' => (int) (memory_get_peak_usage(true) / 1024),
			'limit' => ini_get('memory_limit') ?: '-1',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getPhpData(): array
	{
		return [
			'version' => PHP_VERSION,
			'zts' => PHP_ZTS ? 'enabled' : 'disabled',
			'extensions_loaded' => count(get_loaded_extensions()),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getBenchmarksData(): array
	{
		$file = File::open(Disk::Local, Path::getStorageDir() . self::BENCHMARK_FILE);

		if (!$file->exists()) {
			return [];
		}

		$lines = $file->readLines();

		if ([] === $lines) {
			return [];
		}

		$results = [];

		foreach (array_slice($lines, -20) as $line) {
			$line = trim($line);

			if ('' === $line) {
				continue;
			}

			/** @var array<string, mixed> $data */
			$data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
			$results[] = BenchmarkResult::fromArray($data);
		}

		if ([] === $results) {
			return [];
		}

		$latest = end($results);
		$runsList = array_map(fn (BenchmarkResult $r): array => $r->toArray(), $results);
		$rpsValues = array_map(fn (BenchmarkResult $r): float => $r->requestsPerSec, $results);
		$latValues = array_map(fn (BenchmarkResult $r): float => $r->meanLatencyMs, $results);
		$p99Values = array_map(fn (BenchmarkResult $r): float => $r->p99Ms, $results);
		$concurrency = array_map(fn (BenchmarkResult $r): int => $r->concurrency, $results);

		return [
			'runs' => $runsList,
			'summary' => [
				'total_runs' => count($results),
				'latest_rps' => $latest->requestsPerSec,
				'best_rps' => max($rpsValues),
				'worst_rps' => min($rpsValues),
				'avg_rps' => round(array_sum($rpsValues) / count($rpsValues), 1),
				'avg_latency_ms' => round(array_sum($latValues) / count($latValues), 2),
				'avg_p99_ms' => round(array_sum($p99Values) / count($p99Values), 1),
				'max_concurrency_tested' => max($concurrency),
			],
		];
	}
}
