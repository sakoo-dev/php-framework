<?php

declare(strict_types=1);

namespace App\AI\Neuron\Middleware;

use App\AI\Mcp\McpTokenCalculator;
use App\AI\Neuron\Optimizer\PromptOptimizerInterface;
use App\AI\Neuron\Optimizer\ToolResultNormalizerRegistry;
use App\AI\Neuron\State\ToolResultDto;
use App\AI\Neuron\State\WorkflowStateAccessor;
use NeuronAI\Agent\Events\AIInferenceEvent;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\WorkflowState;

/**
 * Intercepts tool results in workflow state and normalizes them to reduce token usage.
 * Uses DTOs for type-safe state access instead of raw array manipulation.
 */
final readonly class ToolResultOptimizationMiddleware implements WorkflowMiddleware
{
	private const int MAX_TOOL_OUTPUT_LENGTH = 10000;

	public function __construct(
		private ToolResultNormalizerRegistry $registry,
		private PromptOptimizerInterface $promptOptimizer,
		private McpTokenCalculator $tokenCalculator,
	) {}

	public function before(NodeInterface $node, Event $event, WorkflowState $state): void
	{
		$accessor = WorkflowStateAccessor::from($state);
		$this->optimizeInferenceEvent($event, $accessor);

		$toolResults = $accessor->getToolResults();

		if ($toolResults->isEmpty()) {
			return;
		}

		$tokensBefore = 0;
		$tokensAfter = 0;

		$optimized = $toolResults->mapResults(
			function (ToolResultDto $result) use (&$tokensBefore, &$tokensAfter): ToolResultDto {
				$originalTokens = $result->estimateTokenCount($this->tokenCalculator);
				$tokensBefore += $originalTokens;

				$normalizedOutput = $this->registry->normalize(
					$result->toolName,
					$result->output
				);

				$finalOutput = $this->promptOptimizer->optimize(
					$normalizedOutput,
					self::MAX_TOOL_OUTPUT_LENGTH
				);

				$optimizedResult = $result->withOutput($finalOutput);
				$tokensAfter += $optimizedResult->estimateTokenCount($this->tokenCalculator);

				return $optimizedResult;
			}
		);

		$accessor->setToolResults($optimized);

		if ($tokensBefore > 0) {
			$currentStats = $accessor->getOptimizationStats();
			$updatedStats = $currentStats->withAdditionalTokens($tokensBefore, $tokensAfter);
			$accessor->setOptimizationStats($updatedStats);
		}
	}

	public function after(NodeInterface $node, mixed $result, WorkflowState $state): void {}

	private function optimizeInferenceEvent(Event $event, WorkflowStateAccessor $accessor): void
	{
		if (!$event instanceof AIInferenceEvent) {
			return;
		}

		$tokensBefore = 0;
		$tokensAfter = 0;

		foreach ($event->getMessages() as $message) {
			if (!$message instanceof ToolResultMessage) {
				continue;
			}

			foreach ($message->getTools() as $tool) {
				$stats = $this->optimizeTool($tool);

				if (null === $stats) {
					continue;
				}

				$tokensBefore += $stats['before'];
				$tokensAfter += $stats['after'];
			}
		}

		if ($tokensBefore > 0) {
			$currentStats = $accessor->getOptimizationStats();
			$updatedStats = $currentStats->withAdditionalTokens($tokensBefore, $tokensAfter);
			$accessor->setOptimizationStats($updatedStats);
		}
	}

	/**
	 * @return null|array{before: int, after: int}
	 */
	private function optimizeTool(ToolInterface $tool): ?array
	{
		if (!$tool instanceof Tool || !$this->registry->has($tool->getName())) {
			return null;
		}

		$rawOutput = $tool->getResult();
		$originalTokens = $this->estimateTokens($rawOutput);
		$normalizedOutput = $this->normalizeOutputPayload($tool->getName(), $rawOutput);

		$tool->setResult($normalizedOutput);

		return [
			'before' => $originalTokens,
			'after' => $this->estimateTokens($normalizedOutput),
		];
	}

	private function normalizeOutputPayload(string $toolName, string $rawOutput): string
	{
		$decoded = json_decode($rawOutput, true);

		if (!is_array($decoded)) {
			$normalized = $this->registry->normalize($toolName, $rawOutput);

			return $this->promptOptimizer->optimize($normalized, self::MAX_TOOL_OUTPUT_LENGTH);
		}

		$changed = false;

		foreach ($decoded as $index => $item) {
			if (!is_array($item) || !isset($item['text']) || !is_string($item['text'])) {
				continue;
			}

			$normalized = $this->registry->normalize($toolName, $item['text']);
			$optimized = $this->promptOptimizer->optimize($normalized, self::MAX_TOOL_OUTPUT_LENGTH);

			if ($optimized === $item['text']) {
				continue;
			}

			$item['text'] = $optimized;
			$decoded[$index] = $item;
			$changed = true;
		}

		if (!$changed) {
			return $rawOutput;
		}

		return json_encode($decoded, \JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	private function estimateTokens(string $text): int
	{
		return (new McpTokenCalculator())->countText($text);
	}
}
