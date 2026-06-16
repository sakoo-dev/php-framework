<?php

declare(strict_types=1);

namespace App\AI\Neuron\Middleware;

use App\AI\Neuron\Optimizer\ToolResultPruningStrategyInterface;
use NeuronAI\Agent\Events\AIInferenceEvent;
use NeuronAI\Agent\Nodes\ChatNode;
use NeuronAI\Agent\Nodes\StreamingNode;
use NeuronAI\Chat\Messages\ToolResultMessage;
use NeuronAI\Tools\Tool;
use NeuronAI\Workflow\Events\Event;
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use NeuronAI\Workflow\NodeInterface;
use NeuronAI\Workflow\WorkflowState;

/**
 * Prunes tool results in AIInferenceEvent messages BEFORE they are added to ChatHistory.
 * Removes older or low-priority tool results to prevent context window overflow.
 */
final readonly class ToolResultPruningMiddleware implements WorkflowMiddleware
{
	public function __construct(private ToolResultPruningStrategyInterface $strategy) {}

	public function before(NodeInterface $node, Event $event, WorkflowState $state): void
	{
		if (!$this->shouldPrune($node, $event)) {
			return;
		}

		/** @var AIInferenceEvent $event */
		$messages = $event->getMessages();

		if (empty($messages)) {
			return;
		}

		$toolResultMessages = array_filter(
			$messages,
			static fn (mixed $m): bool => $m instanceof ToolResultMessage
		);

		if (empty($toolResultMessages)) {
			return;
		}

		$rawResults = $this->extractRawResults($toolResultMessages);
		$pruned = $this->strategy->prune($rawResults);

		if (count($pruned) === count($rawResults)) {
			return;
		}

		$keptCallIds = array_column($pruned, 'call_id');

		foreach ($messages as $message) {
			if (!$message instanceof ToolResultMessage) {
				continue;
			}

			$remainingTools = array_values(array_filter(
				$message->getTools(),
				static fn (mixed $tool): bool => $tool instanceof Tool
					&& in_array($tool->getCallId(), $keptCallIds, true)
			));

			$this->replaceTools($message, $remainingTools);
		}
	}

	public function after(NodeInterface $node, mixed $result, WorkflowState $state): void {}

	private function shouldPrune(NodeInterface $node, Event $event): bool
	{
		if (!$event instanceof AIInferenceEvent) {
			return false;
		}

		return $node instanceof ChatNode || $node instanceof StreamingNode;
	}

	/**
	 * @param ToolResultMessage[] $messages
	 *
	 * @return array<int, array{tool_name: string, call_id: null|string, output: string, timestamp: int}>
	 */
	private function extractRawResults(array $messages): array
	{
		$results = [];

		foreach ($messages as $message) {
			foreach ($message->getTools() as $tool) {
				if (!$tool instanceof Tool) {
					continue;
				}

				$results[] = [
					'tool_name' => $tool->getName(),
					'call_id' => $tool->getCallId(),
					'output' => $tool->getResult(),
					'timestamp' => time(),
				];
			}
		}

		return $results;
	}

	/**
	 * @param Tool[] $tools
	 */
	private function replaceTools(ToolResultMessage $message, array $tools): void
	{
		$reflection = new \ReflectionProperty(ToolResultMessage::class, 'tools');
		$reflection->setAccessible(true);
		$reflection->setValue($message, $tools);
	}
}
