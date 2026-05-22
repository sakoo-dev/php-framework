<?php

declare(strict_types=1);

namespace App\AI\Neuron\Throttle;

/**
 * Immutable configuration for ThrottleMiddleware.
 *
 * maxRequests is the call ceiling within windowSeconds for a given composite
 * key (agentName + userId). The defaults are intentionally conservative —
 * tune per agent in AIServiceLoader.
 */
final readonly class ThrottleConfig
{
	public function __construct(
		public readonly int $maxRequests = 60,
		public readonly int $windowSeconds = 60,
	) {}
}
