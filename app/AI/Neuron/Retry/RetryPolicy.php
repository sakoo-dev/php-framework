<?php

declare(strict_types=1);

namespace App\AI\Neuron\Retry;

use App\AI\Neuron\Exception\NonRetryableExceptionInterface;

/**
 * Immutable configuration for retry-with-exponential-backoff behaviour.
 *
 * maxAttempts — total number of tries including the first (minimum 1).
 * baseDelayMs — delay in milliseconds before the first retry.
 * multiplier — factor applied to baseDelayMs on each subsequent retry.
 *
 * Example: maxAttempts=3, baseDelayMs=200, multiplier=2.0
 *   attempt 1 (no delay) → attempt 2 (200 ms) → attempt 3 (400 ms) → throw
 *
 * Exceptions tagged with NonRetryableExceptionInterface are never retried,
 * regardless of maxAttempts (e.g. circuit open, throttle exceeded).
 */
final readonly class RetryPolicy
{
	public function __construct(
		public int $maxAttempts = 3,
		public int $baseDelayMs = 200,
		public float $multiplier = 2.0,
	) {}

	public function delayMsForAttempt(int $attempt): int
	{
		return (int) ($this->baseDelayMs * ($this->multiplier ** ($attempt - 1)));
	}

	public function isRetryable(\Throwable $e): bool
	{
		return !$e instanceof NonRetryableExceptionInterface;
	}
}
