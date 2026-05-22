<?php

declare(strict_types=1);

namespace App\AI\Metric;

/**
 * Immutable record of a single agent inference turn.
 *
 * Every field is populated at observation time; fields that cannot yet be known
 * (priceIrt, qualityScore, feedback) are nullable and may be filled later via
 * a separate update path once the infrastructure exists. The sessionId field is
 * the correlation key that ties this entry to a ChatHistory file and any audit
 * records written by the privacy/security layer (Task 4).
 */
final readonly class MetricEntry
{
	public function __construct(
		public string $sessionId,
		public string $timestamp,
		public string $agent,
		public string $model,
		public string $provider,
		public MetricSource $source,
		public int $tokensIn,
		public int $tokensOut,
		public float $priceUsd,
		public ?float $priceIrt,
		public int $latencyMs,
		public ?float $qualityScore,
		public ?string $feedback,
	) {}

	/** @return array<string, mixed> */
	public function toArray(): array
	{
		return [
			'sessionId' => $this->sessionId,
			'timestamp' => $this->timestamp,
			'agent' => $this->agent,
			'model' => $this->model,
			'provider' => $this->provider,
			'source' => $this->source->value,
			'tokensIn' => $this->tokensIn,
			'tokensOut' => $this->tokensOut,
			'priceUsd' => $this->priceUsd,
			'priceIrt' => $this->priceIrt,
			'latencyMs' => $this->latencyMs,
			'qualityScore' => $this->qualityScore,
			'feedback' => $this->feedback,
		];
	}
}
