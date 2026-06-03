<?php

declare(strict_types=1);

namespace App\AI\Neuron\Metric;

/**
 * No-op evaluator used when no quality scoring strategy is configured.
 * Bound by default in AIServiceLoader; replace it with a concrete implementation
 * that calls an LLM judge or applies heuristics once the infrastructure exists.
 */
final class NullQualityEvaluator implements QualityEvaluatorInterface
{
	public function evaluate(string $prompt, string $response): ?float
	{
		return null;
	}
}
