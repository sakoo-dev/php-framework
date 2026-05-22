<?php

declare(strict_types=1);

namespace App\AI\Neuron\Cache;

/**
 * Port for reading and writing cached LLM responses keyed by a deterministic
 * hash of (model, systemPrompt, userMessages).
 *
 * The stored value is a JSON string so the adapter can be swapped to Redis,
 * Memcached, or a database without changing the decorator.
 */
interface CacheStorageInterface
{
	public function get(string $key): ?string;

	public function set(string $key, string $value, int $ttlSeconds): void;

	public function has(string $key): bool;

	public function delete(string $key): void;
}
