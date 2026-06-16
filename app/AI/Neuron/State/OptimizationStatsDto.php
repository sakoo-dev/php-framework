<?php

declare(strict_types=1);

namespace App\AI\Neuron\State;

/**
 * Strongly-typed DTO for optimization statistics tracked in workflow state.
 * Provides transparent read/write access to token usage metrics.
 */
final readonly class OptimizationStatsDto
{
	private function __construct(
		public int $tokensBefore,
		public int $tokensAfter,
	) {}

	public static function create(int $tokensBefore, int $tokensAfter): self
	{
		return new self($tokensBefore, $tokensAfter);
	}

	public static function fromState(mixed $rawData): self
	{
		if (!is_array($rawData)) {
			return self::empty();
		}

		$before = is_int($rawData['tokens_before'] ?? null) ? $rawData['tokens_before'] : 0;
		$after = is_int($rawData['tokens_after'] ?? null) ? $rawData['tokens_after'] : 0;

		return new self($before, $after);
	}

	public static function empty(): self
	{
		return new self(0, 0);
	}

	public function withAdditionalTokens(int $before, int $after): self
	{
		return new self(
			$this->tokensBefore + $before,
			$this->tokensAfter + $after,
		);
	}

	public function getSavings(): int
	{
		return max(0, $this->tokensBefore - $this->tokensAfter);
	}

	public function getReductionPercentage(): float
	{
		if (0 === $this->tokensBefore) {
			return 0.0;
		}

		return ($this->getSavings() / $this->tokensBefore) * 100;
	}

	/**
	 * @return array{tokens_before: int, tokens_after: int}
	 */
	public function toArray(): array
	{
		return [
			'tokens_before' => $this->tokensBefore,
			'tokens_after' => $this->tokensAfter,
		];
	}

	public function isEmpty(): bool
	{
		return 0 === $this->tokensBefore && 0 === $this->tokensAfter;
	}
}
