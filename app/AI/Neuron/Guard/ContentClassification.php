<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

/**
 * Classifies content by sensitivity level, ordered from least to most sensitive.
 *
 * Public     — safe for all audiences; no restrictions.
 * Internal   — acceptable for internal use; logged but not blocked.
 * Confidential — contains PII or sensitive data; PII is masked before reaching LLM.
 * Restricted — illegal, unethical, or abusive content; inference is blocked immediately.
 */
enum ContentClassification: string
{
	case Public = 'Public';
	case Internal = 'Internal';
	case Confidential = 'Confidential';
	case Restricted = 'Restricted';

	public function isBlocking(): bool
	{
		return self::Restricted === $this;
	}

	public function severity(): int
	{
		return match ($this) {
			self::Public => 0,
			self::Internal => 1,
			self::Confidential => 2,
			self::Restricted => 3,
		};
	}

	public function isMoreSevereThan(self $other): bool
	{
		return $this->severity() > $other->severity();
	}
}
