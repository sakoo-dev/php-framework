<?php

declare(strict_types=1);

namespace App\AI\Neuron\Evals\Guard\Assertions;

use App\AI\Neuron\Guard\DetectionResult;
use NeuronAI\Evaluation\AssertionResult;
use NeuronAI\Evaluation\Assertions\AbstractAssertion;

/**
 * Asserts that a DetectionResult contains a specific reason substring.
 *
 * Used to verify that the correct detector category fired, not just that
 * something was flagged. Makes eval failures pinpoint exactly which pattern
 * group was wrong.
 */
final class HasReason extends AbstractAssertion
{
	public function __construct(
		private readonly string $expectedSubstring,
	) {}

	public function evaluate(mixed $actual): AssertionResult
	{
		if (!$actual instanceof DetectionResult) {
			return AssertionResult::fail(0.0, 'Expected DetectionResult, got ' . get_debug_type($actual));
		}

		$combined = $actual->combinedReason();

		if (str_contains($combined, $this->expectedSubstring)) {
			return AssertionResult::pass(1.0);
		}

		return AssertionResult::fail(
			0.0,
			sprintf(
				'Expected reason to contain "%s". Got: "%s"',
				$this->expectedSubstring,
				$combined,
			),
		);
	}
}
