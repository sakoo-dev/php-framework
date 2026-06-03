<?php

declare(strict_types=1);

namespace App\AI\Neuron\Evals\Guard\Assertions;

use App\AI\Neuron\Guard\ContentClassification;
use App\AI\Neuron\Guard\DetectionResult;
use NeuronAI\Evaluation\AssertionResult;
use NeuronAI\Evaluation\Assertions\AbstractAssertion;

/**
 * Asserts that a DetectionResult is flagged with the expected classification.
 *
 * Passes when the detector matched and produced at least the expected
 * ContentClassification severity. Used to verify true-positive detection.
 */
final class IsFlaggedAs extends AbstractAssertion
{
	public function __construct(
		private readonly ContentClassification $expected,
	) {}

	public function evaluate(mixed $actual): AssertionResult
	{
		if (!$actual instanceof DetectionResult) {
			return AssertionResult::fail(0.0, 'Expected DetectionResult, got ' . get_debug_type($actual));
		}

		if (!$actual->matched) {
			return AssertionResult::fail(0.0, 'Expected a match but result was clean');
		}

		if ($actual->classification === $this->expected || $actual->classification->isMoreSevereThan($this->expected)) {
			return AssertionResult::pass(1.0);
		}

		return AssertionResult::fail(
			0.0,
			sprintf(
				'Expected classification %s but got %s. Reasons: %s',
				$this->expected->value,
				$actual->classification->value,
				$actual->combinedReason(),
			),
		);
	}
}
