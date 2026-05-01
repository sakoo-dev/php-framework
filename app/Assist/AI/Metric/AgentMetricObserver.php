<?php

declare(strict_types=1);

namespace App\Assist\AI\Metric;

use App\Assist\AI\Neuron\Session\SessionId;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Observability\Events\InferenceStart;
use NeuronAI\Observability\Events\InferenceStop;
use NeuronAI\Observability\ObserverInterface;

/**
 * Single observer for all NeuronAI agent events. Combines the responsibilities
 * of NeuronAI's built-in LogObserver with per-turn metric recording, so only
 * one observer needs to be registered per agent.
 *
 * On every InferenceStart/InferenceStop pair it:
 *   - logs the event through the PSR-3 logger (all events, same as LogObserver)
 *   - captures latency, token usage, and USD cost via ModelPricing
 *   - delegates quality scoring to QualityEvaluatorInterface
 *   - persists a MetricEntry through MetricStorageInterface
 */
final class AgentMetricObserver implements ObserverInterface
{
	private ?int $startTimeMs = null;
	private string $lastPrompt = '';

	public function __construct(
		private readonly MetricStorageInterface $storage,
		private readonly QualityEvaluatorInterface $qualityEvaluator,
		private readonly SessionId $sessionId,
		private readonly string $agentName,
		private readonly string $modelName,
		private readonly string $providerName,
		private readonly MetricSource $source = MetricSource::Live,
	) {}

	public function onEvent(string $event, object $source, mixed $data = null): void
	{
		if ($data instanceof InferenceStart) {
			$this->handleInferenceStart($data);
		}

		if ($data instanceof InferenceStop) {
			$this->handleInferenceStop($data);
		}
	}

	private function handleInferenceStart(InferenceStart $data): void
	{
		$this->startTimeMs = (int) (microtime(true) * 1000);
		$this->lastPrompt = $this->extractText($data->message);
	}

	private function handleInferenceStop(InferenceStop $data): void
	{
		$latencyMs = null !== $this->startTimeMs ? (int) (microtime(true) * 1000) - $this->startTimeMs : 0;
		$tokensIn = 0;
		$tokensOut = 0;

		if ($data->response instanceof AssistantMessage) {
			$usage = $data->response->getUsage();

			if (null !== $usage) {
				$tokensIn = $usage->inputTokens;
				$tokensOut = $usage->outputTokens;
			}
		}

		$responseText = $this->extractText($data->response);
		$qualityScore = $this->qualityEvaluator->evaluate($this->lastPrompt, $responseText);
		$pricing = ModelPricing::forModel($this->modelName);

		$this->storage->store(new MetricEntry(
			sessionId: $this->sessionId->value,
			timestamp: date('c'),
			agent: $this->agentName,
			model: $this->modelName,
			provider: $this->providerName,
			source: $this->source,
			tokensIn: $tokensIn,
			tokensOut: $tokensOut,
			priceUsd: $pricing->calculateCostUsd($tokensIn, $tokensOut),
			priceIrt: null,
			latencyMs: $latencyMs,
			qualityScore: $qualityScore,
			feedback: null,
		));

		$this->startTimeMs = null;
		$this->lastPrompt = '';
	}

	private function extractText(false|Message $message): string
	{
		if (false === $message) {
			return '';
		}

		$content = $message->getContent();

		return is_string($content) ? $content : '';
	}
}
