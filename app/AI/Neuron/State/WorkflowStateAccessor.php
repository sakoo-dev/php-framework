<?php

declare(strict_types=1);

namespace App\AI\Neuron\State;

use NeuronAI\Workflow\WorkflowState;

/**
 * Type-safe accessor for workflow state using DTOs.
 * Provides strongly-typed read/write operations instead of raw array manipulation.
 */
final readonly class WorkflowStateAccessor
{
	private const KEY_TOOL_RESULTS = 'tool_results';
	private const KEY_OPTIMIZATION_STATS = 'optimization_stats';

	public function __construct(private WorkflowState $state) {}

	public static function from(WorkflowState $state): self
	{
		return new self($state);
	}

	public function getToolResults(): ToolResultsStateDto
	{
		return ToolResultsStateDto::fromState(
			$this->state->get(self::KEY_TOOL_RESULTS)
		);
	}

	public function setToolResults(ToolResultsStateDto $dto): void
	{
		if ($dto->isEmpty()) {
			$this->state->delete(self::KEY_TOOL_RESULTS);

			return;
		}

		$this->state->set(self::KEY_TOOL_RESULTS, $dto->toArray());
	}

	public function hasToolResults(): bool
	{
		return $this->state->has(self::KEY_TOOL_RESULTS);
	}

	public function clearToolResults(): void
	{
		$this->state->delete(self::KEY_TOOL_RESULTS);
	}

	public function getOptimizationStats(): OptimizationStatsDto
	{
		return OptimizationStatsDto::fromState(
			$this->state->get(self::KEY_OPTIMIZATION_STATS)
		);
	}

	public function setOptimizationStats(OptimizationStatsDto $dto): void
	{
		if ($dto->isEmpty()) {
			$this->state->delete(self::KEY_OPTIMIZATION_STATS);

			return;
		}

		$this->state->set(self::KEY_OPTIMIZATION_STATS, $dto->toArray());
	}

	public function hasOptimizationStats(): bool
	{
		return $this->state->has(self::KEY_OPTIMIZATION_STATS);
	}

	public function clearOptimizationStats(): void
	{
		$this->state->delete(self::KEY_OPTIMIZATION_STATS);
	}

	public function getRawState(): WorkflowState
	{
		return $this->state;
	}
}
