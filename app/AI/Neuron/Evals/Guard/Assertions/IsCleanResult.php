<?php

declare(strict_types=1);

namespace App\AI\Neuron\Evals\Guard\Assertions;

use App\AI\Neuron\Guard\DetectionResult;
use NeuronAI\Evaluation\AssertionResult;
use NeuronAI\Evaluation\Assertions\AbstractAssertion;

/**
 * Asserts that a DetectionResult is clean (not matched).
 *
 * Passes when the detector found nothing to flag. Used to verify that
 * legitimate input does not trigger false positives.
 */
final class IsCleanResult extends AbstractAssertion
{
	public function evaluate(mixed $actual): AssertionResult
	{
		if (!$actual instanceof DetectionResult) {
			return AssertionResult::fail(0.0, 'Expected DetectionResult, got ' . get_debug_type($actual));
		}

		if (!$actual->matched) {
			return AssertionResult::pass(1.0);
		}

		return AssertionResult::fail(0.0, 'Expected clean result but got: ' . $actual->combinedReason());
	}
}
