<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard\Exception;

use App\AI\Neuron\Guard\ContentClassification;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown by GuardrailPipeline when a Restricted classification is detected.
 *
 * Carry the classification and reason so the caller can build a user-facing
 * message or audit entry without re-inspecting the original text.
 */
final class GuardViolationException extends Exception
{
	public function __construct(
		public readonly ContentClassification $classification,
		public readonly string $reason,
	) {
		parent::__construct(
			message: sprintf('[%s] Content blocked: %s', $classification->value, $reason),
		);
	}
}
