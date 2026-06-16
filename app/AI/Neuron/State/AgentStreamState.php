<?php

declare(strict_types=1);

namespace App\AI\Neuron\State;

/**
 * Encapsulates the state returned from an agent stream execution,
 * including optimization statistics for token usage analysis.
 */
final readonly class AgentStreamState
{
	public function __construct(
		public int $tokensBefore,
		public int $tokensAfter,
	) {}

	/**
	 * @param array<array-key, mixed> $data
	 */
	public static function fromArray(array $data): self
	{
		$optimizationStats = $data['optimization_stats'] ?? [];

		if (!is_array($optimizationStats)) {
			return new self(0, 0);
		}

		$tokensBefore = $optimizationStats['tokens_before'] ?? 0;
		$tokensAfter = $optimizationStats['tokens_after'] ?? 0;

		return new self(
			tokensBefore: is_int($tokensBefore) ? $tokensBefore : 0,
			tokensAfter: is_int($tokensAfter) ? $tokensAfter : 0,
		);
	}

	public function tokensSaved(): int
	{
		return $this->tokensBefore - $this->tokensAfter;
	}

	public function savingsPercent(): float
	{
		if ($this->tokensBefore <= 0) {
			return 0.0;
		}

		return round(($this->tokensSaved() / $this->tokensBefore) * 100, 1);
	}

	public function hasOptimization(): bool
	{
		return $this->tokensBefore > 0 && $this->tokensAfter > 0;
	}
}
