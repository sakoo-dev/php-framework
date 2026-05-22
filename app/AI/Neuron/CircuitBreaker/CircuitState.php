<?php

declare(strict_types=1);

namespace App\AI\Neuron\CircuitBreaker;

/**
 * Tracks the three states of a circuit breaker per provider key.
 *
 * Closed  → normal operation, requests pass through.
 * Open    → provider is considered down; requests are rejected immediately.
 * HalfOpen → a probe request is allowed to test if the provider recovered.
 */
enum CircuitState: string
{
	case Closed = 'closed';
	case Open = 'open';
	case HalfOpen = 'half_open';
}
