<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Strategy;

use App\AI\Neuron\Optimizer\ToolResultPruningStrategyInterface;

/**
 * Keeps only tool results that match specified tool names.
 * Useful for filtering out verbose tools while preserving critical ones.
 */
final readonly class SelectiveToolResultsPruningStrategy implements ToolResultPruningStrategyInterface
{
	/**
	 * @param string[] $keepToolNames Tool names to preserve
	 */
	public function __construct(private array $keepToolNames) {}

	public function prune(array $toolResults): array
	{
		return array_values(array_filter(
			$toolResults,
			fn (array $result): bool => in_array($result['tool_name'], $this->keepToolNames, true)
		));
	}
}
