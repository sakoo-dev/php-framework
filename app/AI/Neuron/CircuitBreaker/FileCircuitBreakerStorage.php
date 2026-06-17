<?php

declare(strict_types=1);

namespace App\AI\Neuron\CircuitBreaker;

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

/**
 * File-based circuit-breaker state adapter.
 *
 * Each provider key gets a JSON file at storage/ai/circuit-breaker/{key}.json
 * containing failure count, last-failure timestamp, current state, and a
 * probe_claimed flag that gates the single HalfOpen probe call.
 *
 * Uses exclusive file locking (LOCK_EX) in claimProbe() to prevent race conditions
 * in multi-process environments. Other operations use optimistic concurrency — they
 * are idempotent enough that occasional lost updates are acceptable (e.g., missing
 * one failure increment out of five still opens the circuit).
 *
 * For high-throughput distributed systems, consider RedisCircuitBreakerStorage with
 * Lua scripts or atomic SET NX operations for better performance.
 */
final class FileCircuitBreakerStorage implements CircuitBreakerStorageInterface
{
	public function __construct(
		private readonly int $failureThreshold = 5,
		private readonly int $openWindowSeconds = 60,
	) {}

	public function getState(string $key): CircuitState
	{
		$data = $this->read($key);
		$rawState = is_string($data['state'] ?? null) ? (string) $data['state'] : CircuitState::Closed->value;
		$state = CircuitState::from($rawState);

		if (CircuitState::Open === $state && $this->cooldownElapsed($data)) {
			$this->transition($key, $data, CircuitState::HalfOpen);

			return CircuitState::HalfOpen;
		}

		return $state;
	}

	public function getFailureCount(string $key): int
	{
		$value = $this->read($key)['failures'] ?? null;

		return is_int($value) ? $value : 0;
	}

	public function recordFailure(string $key): void
	{
		$data = $this->read($key);
		$state = CircuitState::from(
			is_string($data['state'] ?? null) ? (string) $data['state'] : CircuitState::Closed->value,
		);

		if (CircuitState::HalfOpen === $state) {
			$data['state'] = CircuitState::Open->value;
			$data['last_failure'] = time();
			$data['probe_claimed'] = false;
			$this->write($key, $data);

			return;
		}

		$failures = is_int($data['failures'] ?? null) ? (int) $data['failures'] : 0;
		$data['failures'] = $failures + 1;
		$data['last_failure'] = time();

		if ((int) $data['failures'] >= $this->failureThreshold) {
			$data['state'] = CircuitState::Open->value;
			$data['probe_claimed'] = false;
		}

		$this->write($key, $data);
	}

	public function recordSuccess(string $key): void
	{
		$this->write($key, [
			'state' => CircuitState::Closed->value,
			'failures' => 0,
			'last_failure' => null,
			'probe_claimed' => false,
		]);
	}

	public function claimProbe(string $key): bool
	{
		$path = $this->filePath($key);
		$handle = @fopen($path, 'c+');

		if (!$handle) {
			return false;
		}

		if (!flock($handle, LOCK_EX)) {
			fclose($handle);

			return false;
		}

		try {
			$content = stream_get_contents($handle);
			$data = $content ? json_decode($content, true) : [];

			if (!is_array($data)) {
				$data = [];
			}

			if (true === ($data['probe_claimed'] ?? false)) {
				return false;
			}

			$data['probe_claimed'] = true;

			$encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

			if (false === $encoded) {
				return false;
			}

			ftruncate($handle, 0);
			rewind($handle);
			fwrite($handle, $encoded);
			fflush($handle);

			return true;
		} finally {
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}

	/** @param array<string, mixed> $data */
	private function cooldownElapsed(array $data): bool
	{
		$lastFailure = $data['last_failure'] ?? null;

		return is_int($lastFailure) && (time() - $lastFailure) >= $this->openWindowSeconds;
	}

	/** @param array<string, mixed> $data */
	private function transition(string $key, array $data, CircuitState $state): void
	{
		$data['state'] = $state->value;
		$data['probe_claimed'] = false;
		$this->write($key, $data);
	}

	/** @return array<string, mixed> */
	private function read(string $key): array
	{
		$file = File::open(Disk::Local, $this->filePath($key));

		if (!$file->exists() || $file->isDir()) {
			return [];
		}

		$content = implode(PHP_EOL, $file->readLines());

		if (!$content) {
			return [];
		}

		$raw = json_decode($content, true);

		if (!is_array($raw)) {
			return [];
		}

		$result = [];

		foreach ($raw as $k => $v) {
			if (is_string($k)) {
				$result[$k] = $v;
			}
		}

		return $result;
	}

	/** @param array<string, mixed> $data */
	private function write(string $key, array $data): void
	{
		$file = File::open(Disk::Local, $this->filePath($key));
		$file->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
	}

	private function filePath(string $key): string
	{
		return Path::getStorageDir() . '/ai/circuit-breaker/' . preg_replace('/[^a-z0-9_\-]/', '_', strtolower($key)) . '.json';
	}
}
