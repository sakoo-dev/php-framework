<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

use App\AI\Neuron\Guard\Exception\GuardViolationException;

/**
 * Runs all registered DetectionStrategyInterface instances in sequence and
 * aggregates their results into a single PipelineResult.
 *
 * Each strategy receives the processedText produced by the previous one, so
 * masking strategies (PiiMaskingDetector) sanitise the text before sensitive
 * detectors inspect it. The pipeline tracks the highest severity classification
 * seen across all strategies.
 *
 * When any strategy returns Restricted, the pipeline throws GuardViolationException
 * immediately — remaining strategies are skipped to avoid processing harmful content
 * further. For all non-blocking classifications the pipeline runs to completion and
 * returns the aggregated result.
 */
final class GuardrailPipeline
{
	/** @param DetectionStrategyInterface[] $strategies */
	public function __construct(private readonly array $strategies) {}

	/**
	 * @throws GuardViolationException
	 */
	public function run(string $text): PipelineResult
	{
		$current = $text;
		$topClassification = ContentClassification::Public;
		/** @var string[] $reasons */
		$reasons = [];

		foreach ($this->strategies as $strategy) {
			/** @var DetectionResult $result */
			$result = $strategy->detect($current);

			if (!$result->matched) {
				continue;
			}

			foreach ($result->reasons as $reason) {
				$reasons[] = $reason;
			}

			if ($result->classification->isMoreSevereThan($topClassification)) {
				$topClassification = $result->classification;
			}

			if ($result->classification->isBlocking()) {
				throw new GuardViolationException(
					classification: $result->classification,
					reason: $result->combinedReason(),
				);
			}

			$current = $result->processedText;
		}

		return new PipelineResult(
			classification: $topClassification,
			reasons: $reasons,
			safeText: $current,
		);
	}
}
