<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

/**
 * Immutable result produced by a single DetectionStrategyInterface run.
 *
 * When matched is false, classification is Public, reasons is empty, and
 * processedText equals the original input.
 *
 * When matched is true, reasons carries every distinct match reason collected
 * during the detector run — one entry per matched pattern or category. This
 * allows audit logs and pipeline consumers to see the full picture of why a
 * piece of text was flagged, rather than only the first match.
 *
 * processedText may differ from the original when the detector performed
 * in-place masking (e.g. PiiMaskingDetector replaces PII tokens).
 */
final readonly class DetectionResult
{
	/** @param string[] $reasons */
	public function __construct(
		public bool $matched,
		public ContentClassification $classification,
		public array $reasons,
		public string $processedText,
	) {}

	public static function clean(string $text): self
	{
		return new self(
			matched: false,
			classification: ContentClassification::Public,
			reasons: [],
			processedText: $text,
		);
	}

	/**
	 * Creates a matched result with a single reason. Use withReason() to
	 * accumulate additional reasons on subsequent matches within the same run.
	 */
	public static function flag(
		ContentClassification $classification,
		string $reason,
		string $processedText,
	): self {
		return new self(
			matched: true,
			classification: $classification,
			reasons: [$reason],
			processedText: $processedText,
		);
	}

	/**
	 * Returns a new instance with an additional reason appended and the
	 * classification upgraded if the new one is more severe.
	 */
	public function withReason(ContentClassification $classification, string $reason): self
	{
		$highest = $classification->isMoreSevereThan($this->classification)
			? $classification
			: $this->classification;

		return new self(
			matched: true,
			classification: $highest,
			reasons: [...$this->reasons, $reason],
			processedText: $this->processedText,
		);
	}

	/**
	 * Returns a new instance with updated processedText (used after masking).
	 */
	public function withProcessedText(string $processedText): self
	{
		return new self(
			matched: $this->matched,
			classification: $this->classification,
			reasons: $this->reasons,
			processedText: $processedText,
		);
	}

	/**
	 * Returns all reasons joined into a single string for logging and audit entries.
	 */
	public function combinedReason(): string
	{
		return implode('; ', $this->reasons);
	}

	public function reasonCount(): int
	{
		return count($this->reasons);
	}
}
