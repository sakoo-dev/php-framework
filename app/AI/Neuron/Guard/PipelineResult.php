<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

/**
 * Aggregated output of a full GuardrailPipeline run across all strategies.
 *
 * safeText holds the text after all masking strategies have been applied —
 * this is the value that should replace the original text before it reaches
 * the LLM or is written to audit storage.
 */
final readonly class PipelineResult
{
	/** @param string[] $reasons */
	public function __construct(
		public ContentClassification $classification,
		public array $reasons,
		public string $safeText,
	) {}

	public function combinedReason(): string
	{
		return implode('; ', $this->reasons);
	}
}
