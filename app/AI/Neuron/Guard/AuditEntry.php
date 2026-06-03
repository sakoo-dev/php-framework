<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

/**
 * Immutable record written to storage/ai/audit/{YYYY-MM-DD}.jsonl for every
 * guarded inference turn.
 *
 * The raw text is never stored when classification is Restricted — only the
 * reason is persisted to avoid logging harmful content. For all other levels
 * the processedText (already masked by PiiMaskingDetector) is stored.
 */
final readonly class AuditEntry
{
	public function __construct(
		public string $sessionId,
		public string $timestamp,
		public string $agent,
		public string $direction,
		public ContentClassification $classification,
		public string $reason,
		public ?string $safeText,
	) {}

	/** @return array<string, mixed> */
	public function toArray(): array
	{
		return [
			'sessionId' => $this->sessionId,
			'timestamp' => $this->timestamp,
			'agent' => $this->agent,
			'direction' => $this->direction,
			'classification' => $this->classification->value,
			'reason' => $this->reason,
			'safeText' => $this->safeText,
		];
	}
}
