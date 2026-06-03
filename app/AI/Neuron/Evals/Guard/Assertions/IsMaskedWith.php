<?php

declare(strict_types=1);

namespace App\AI\Neuron\Evals\Guard\Assertions;

use App\AI\Neuron\Guard\DetectionResult;
use NeuronAI\Evaluation\AssertionResult;
use NeuronAI\Evaluation\Assertions\AbstractAssertion;

/**
 * Asserts that a DetectionResult's processedText contains the expected mask token
 * and does not contain the original PII value.
 *
 * Used to verify that PiiMaskingDetector replaces sensitive data in-place
 * without leaving the original value in the output.
 */
final class IsMaskedWith extends AbstractAssertion
{
	public function __construct(
		private readonly string $token,
		private readonly string $originalValue,
	) {}

	public function evaluate(mixed $actual): AssertionResult
	{
		if (!$actual instanceof DetectionResult) {
			return AssertionResult::fail(0.0, 'Expected DetectionResult, got ' . get_debug_type($actual));
		}

		if (!$actual->matched) {
			return AssertionResult::fail(0.0, 'Expected a match but result was clean');
		}

		$containsToken = str_contains($actual->processedText, $this->token);
		$leaksOriginal = str_contains($actual->processedText, $this->originalValue);

		if ($containsToken && !$leaksOriginal) {
			return AssertionResult::pass(1.0);
		}

		return AssertionResult::fail(
			0.0,
			sprintf(
				'Expected processedText to contain %s and not contain %s. Got: %s',
				$this->token,
				$this->originalValue,
				$actual->processedText,
			),
		);
	}
}
