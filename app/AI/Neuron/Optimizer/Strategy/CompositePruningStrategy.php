<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Strategy;

use App\AI\Neuron\Optimizer\ToolResultPruningStrategyInterface;

/**
 * Combines multiple pruning strategies in sequence.
 * Each strategy processes the output of the previous one.
 */
final readonly class CompositePruningStrategy implements ToolResultPruningStrategyInterface
{
	/**
	 * @param ToolResultPruningStrategyInterface[] $strategies
	 */
	public function __construct(private array $strategies) {}

	public function prune(array $toolResults): array
	{
		foreach ($this->strategies as $strategy) {
			$toolResults = $strategy->prune($toolResults);
		}

		return $toolResults;
	}
}
