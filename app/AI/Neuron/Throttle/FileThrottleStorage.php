<?php

declare(strict_types=1);

namespace App\AI\Neuron\Throttle;

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

/**
 * File-backed sliding-window throttle.
 *
 * Each composite key gets a JSON file at storage/ai/throttle/{key}.json
 * containing an array of Unix timestamps within the current window.
 * Timestamps outside the window are pruned on every consume() call.
 *
 * Suitable for single-process CLI usage. Replace with a Redis adapter
 * (ZADD/ZCOUNT/ZREMRANGEBYSCORE) for multi-process or high-concurrency use.
 */
final class FileThrottleStorage implements ThrottleStorageInterface
{
	public function consume(string $key, int $maxRequests, int $windowSeconds): bool
	{
		$now = time();
		$timestamps = $this->load($key, $now, $windowSeconds);

		if (count($timestamps) >= $maxRequests) {
			return false;
		}

		$timestamps[] = $now;
		$this->save($key, $timestamps);

		return true;
	}

	public function retryAfter(string $key, int $windowSeconds): int
	{
		$now = time();
		$timestamps = $this->load($key, $now, $windowSeconds);

		if ([] === $timestamps) {
			return 0;
		}

		return max(0, min($timestamps) + $windowSeconds - $now);
	}

	/** @return int[] */
	private function load(string $key, int $now, int $windowSeconds): array
	{
		$file = File::open(Disk::Local, $this->filePath($key));

		if (!$file->exists() || $file->isDir()) {
			return [];
		}

		$raw = json_decode(implode(PHP_EOL, $file->readLines()), true);

		if (!is_array($raw)) {
			return [];
		}

		return array_values(array_filter($raw, fn (mixed $ts): bool => is_int($ts) && $ts >= $now - $windowSeconds));
	}

	/** @param int[] $timestamps */
	private function save(string $key, array $timestamps): void
	{
		$file = File::open(Disk::Local, $this->filePath($key));
		$file->write(json_encode(array_values($timestamps), JSON_UNESCAPED_SLASHES) ?: '');
	}

	private function filePath(string $key): string
	{
		return Path::getStorageDir() . '/ai/throttle/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
	}
}
