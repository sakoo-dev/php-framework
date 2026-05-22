<?php

declare(strict_types=1);

namespace App\AI\Neuron\Throttle;

/**
 * Port for throttle state persistence per composite key.
 * Swap to a Redis adapter (ZADD/ZREMRANGEBYSCORE) for multi-process safety.
 */
interface ThrottleStorageInterface
{
	/**
	 * Attempts to consume one token from the sliding-window bucket for $key.
	 * Returns true when the request is allowed, false when the limit is exceeded.
	 */
	public function consume(string $key, int $maxRequests, int $windowSeconds): bool;

	/**
	 * Returns seconds until the oldest request in the window expires.
	 * Passed to ThrottleLimitExceededException::$retryAfterSeconds.
	 */
	public function retryAfter(string $key, int $windowSeconds): int;
}
