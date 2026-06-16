<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer;

/**
 * Strategy for pruning tool results from chat history to minimize context size.
 * Determines which results to keep vs discard based on relevance and recency.
 */
interface ToolResultPruningStrategyInterface
{
	/**
	 * Filter tool results to keep only essential items.
	 *
	 * @param array<int, array{tool_name: string, call_id: null|string, output: string, timestamp?: int}> $toolResults
	 *
	 * @return array<int, array{tool_name: string, call_id: null|string, output: string, timestamp?: int}>
	 */
	public function prune(array $toolResults): array;
}
