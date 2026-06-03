<?php

declare(strict_types=1);

namespace App\AI\Neuron\Metric;

/**
 * Immutable value object that encodes the public pricing for a known model.
 *
 * Rates are expressed as USD cost per 1 000 000 tokens (the industry standard
 * unit). Named constructors are provided for every model that appears in the
 * codebase; `unknown()` is the safe fallback for any model string not yet
 * catalogued — it returns zero rates so cost calculations stay safe rather than
 * throwing.
 *
 * Add a new named constructor whenever a new model is introduced to the project.
 * Prices are sourced from each provider's public pricing page; update them when
 * providers change their rates.
 */
final readonly class ModelPricing
{
	private function __construct(
		public float $inputPer1MTokens,
		public float $outputPer1MTokens,
	) {}

	public static function forModel(string $model): self
	{
		return match (true) {
			str_contains($model, 'claude-opus-4') => new self(inputPer1MTokens: 15.0, outputPer1MTokens: 75.0),
			str_contains($model, 'claude-sonnet-4') => new self(inputPer1MTokens: 3.0, outputPer1MTokens: 15.0),
			str_contains($model, 'claude-haiku-4') => new self(inputPer1MTokens: 0.8, outputPer1MTokens: 4.0),
			str_contains($model, 'claude-3-5-sonnet') => new self(inputPer1MTokens: 3.0, outputPer1MTokens: 15.0),
			str_contains($model, 'claude-3-5-haiku') => new self(inputPer1MTokens: 0.8, outputPer1MTokens: 4.0),
			str_contains($model, 'claude-3-opus') => new self(inputPer1MTokens: 15.0, outputPer1MTokens: 75.0),
			str_contains($model, 'gpt-4o') => new self(inputPer1MTokens: 2.5, outputPer1MTokens: 10.0),
			str_contains($model, 'gpt-4-turbo') => new self(inputPer1MTokens: 10.0, outputPer1MTokens: 30.0),
			str_contains($model, 'gpt-4') => new self(inputPer1MTokens: 30.0, outputPer1MTokens: 60.0),
			str_contains($model, 'gpt-3.5') => new self(inputPer1MTokens: 0.5, outputPer1MTokens: 1.5),
			str_contains($model, 'gemini-1.5-pro') => new self(inputPer1MTokens: 1.25, outputPer1MTokens: 5.0),
			str_contains($model, 'gemini-1.5-flash') => new self(inputPer1MTokens: 0.075, outputPer1MTokens: 0.3),
			str_contains($model, 'deepseek-chat') => new self(inputPer1MTokens: 0.27, outputPer1MTokens: 1.1),
			str_contains($model, 'deepseek-reasoner') => new self(inputPer1MTokens: 0.55, outputPer1MTokens: 2.19),
			default => new self(inputPer1MTokens: 0.0, outputPer1MTokens: 0.0),
		};
	}

	public function calculateCostUsd(int $tokensIn, int $tokensOut): float
	{
		return round(($tokensIn / 1_000_000) * $this->inputPer1MTokens + ($tokensOut / 1_000_000) * $this->outputPer1MTokens, 8);
	}
}
