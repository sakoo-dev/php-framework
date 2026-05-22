<?php

declare(strict_types=1);

namespace App\AI\Neuron\CircuitBreaker;

/**
 * Port for reading and writing circuit breaker state per provider key.
 * Implement with a file, Redis, or database backend to share state across
 * processes; use the in-memory storage for single-process CLI usage.
 */
interface CircuitBreakerStorageInterface
{
	public function getState(string $key): CircuitState;

	public function getFailureCount(string $key): int;

	public function recordFailure(string $key): void;

	public function recordSuccess(string $key): void;

	/**
	 * Atomically claims the single probe slot for a HalfOpen circuit.
	 *
	 * Returns true once per open window — the first caller allowed to probe.
	 * All subsequent callers receive false and must be rejected, preventing
	 * a thundering herd of probe calls from flooding a still-degraded provider.
	 */
	public function claimProbe(string $key): bool;
}
