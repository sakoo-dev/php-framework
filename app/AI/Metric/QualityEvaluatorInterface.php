<?php

declare(strict_types=1);

namespace App\AI\Metric;

/**
 * Produces a quality score in the range 0–1 for a completed inference turn.
 * The score is stored in MetricEntry::$qualityScore and surfaced in dashboards.
 * Implement a concrete evaluator and bind it in AIServiceLoader to activate scoring.
 */
interface QualityEvaluatorInterface
{
	/**
	 * Returns a score between 0.0 and 1.0, or null when evaluation is not applicable.
	 */
	public function evaluate(string $prompt, string $response): ?float;
}
