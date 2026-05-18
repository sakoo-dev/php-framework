<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Cache;

use App\Assist\AI\Neuron\Cache\Exception\CacheStorageException;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

/**
 * File-based LLM response cache adapter.
 *
 * Each entry is stored as a JSON file under storage/ai/cache/{key}.json
 * with an expiry Unix timestamp. Files past their TTL are treated as misses
 * and deleted on next access so the cache self-prunes.
 */
final class FileCacheStorage implements CacheStorageInterface
{
	public function get(string $key): ?string
	{
		$file = File::open(Disk::Local, $this->filePath($key));

		if (!$file->exists() || $file->isDir()) {
			return null;
		}

		$content = implode(PHP_EOL, $file->readLines());

		if (!$content) {
			return null;
		}

		$data = json_decode($content, true);

		if (!is_array($data)) {
			return null;
		}

		$expiresAt = is_int($data['expires_at'] ?? null) ? (int) $data['expires_at'] : 0;
		$value = is_string($data['value'] ?? null) ? (string) $data['value'] : null;

		if (is_null($value) || time() > $expiresAt) {
			$this->delete($key);

			return null;
		}

		return $value;
	}

	public function set(string $key, string $value, int $ttlSeconds): void
	{
		$file = File::open(Disk::Local, $this->filePath($key));

		$encoded = json_encode(['expires_at' => time() + $ttlSeconds, 'value' => $value], JSON_UNESCAPED_SLASHES);
		throwUnless($encoded, new CacheStorageException("Failed to encode cache entry for key: {$key}"));

		$result = $file->write($encoded);
		throwUnless($result, new CacheStorageException("Failed to encode cache entry for key: {$key}"));
	}

	public function has(string $key): bool
	{
		return !is_null($this->get($key));
	}

	public function delete(string $key): void
	{
		$file = File::open(Disk::Local, $this->filePath($key));
		$file->remove();
	}

	private function filePath(string $key): string
	{
		return Path::getStorageDir() . "/ai/cache/$key.json";
	}
}
