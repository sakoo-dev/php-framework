<?php

declare(strict_types=1);

namespace App\AI\Neuron\State;

use App\AI\Mcp\McpTokenCalculator;

/**
 * Immutable DTO representing a single tool execution result.
 * Enforces type contracts for workflow middleware state.
 */
final readonly class ToolResultDto
{
	private function __construct(
		public string $toolName,
		public string $output,
		public ?\DateTimeImmutable $executedAt,
	) {}

	public static function create(
		string $toolName,
		string $output,
		?\DateTimeImmutable $executedAt = null,
	): self {
		return new self($toolName, $output, $executedAt);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromArray(array $data): ?self
	{
		if (!isset($data['tool_name']) || !is_string($data['tool_name'])) {
			return null;
		}

		if (!isset($data['output']) || !is_string($data['output'])) {
			return null;
		}

		$timestamp = null;

		if (isset($data['timestamp'])) {
			if (is_int($data['timestamp'])) {
				$timestamp = (new \DateTimeImmutable())->setTimestamp($data['timestamp']);
			}
		}

		return new self($data['tool_name'], $data['output'], $timestamp);
	}

	public function withOutput(string $newOutput): self
	{
		return new self($this->toolName, $newOutput, $this->executedAt);
	}

	public function withTimestamp(\DateTimeImmutable $timestamp): self
	{
		return new self($this->toolName, $this->output, $timestamp);
	}

	/**
	 * @return array{tool_name: string, output: string, timestamp?: int}
	 */
	public function toArray(): array
	{
		$data = [
			'tool_name' => $this->toolName,
			'output' => $this->output,
		];

		if (null !== $this->executedAt) {
			$data['timestamp'] = $this->executedAt->getTimestamp();
		}

		return $data;
	}

	public function estimateTokenCount(McpTokenCalculator $calculator): int
	{
		return $calculator->countText($this->output);
	}
}
