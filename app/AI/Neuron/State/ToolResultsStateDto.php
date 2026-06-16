<?php

declare(strict_types=1);

namespace App\AI\Neuron\State;

/**
 * Strongly-typed DTO for tool results in workflow state.
 * Replaces raw array manipulation with explicit contracts.
 */
final readonly class ToolResultsStateDto
{
	/**
	 * @param list<ToolResultDto> $results
	 */
	private function __construct(public array $results) {}

	/**
	 * @param list<ToolResultDto> $results
	 */
	public static function fromResults(array $results): self
	{
		return new self($results);
	}

	public static function fromState(mixed $rawData): self
	{
		if (!is_array($rawData)) {
			return new self([]);
		}

		$results = [];

		foreach ($rawData as $item) {
			if (!is_array($item)) {
				continue;
			}

			/** @var array<string, mixed> $validatedItem */
			$validatedItem = $item;
			$dto = ToolResultDto::fromArray($validatedItem);

			if (null !== $dto) {
				$results[] = $dto;
			}
		}

		return new self($results);
	}

	public static function empty(): self
	{
		return new self([]);
	}

	public function isEmpty(): bool
	{
		return empty($this->results);
	}

	public function count(): int
	{
		return count($this->results);
	}

	/**
	 * @return list<ToolResultDto>
	 */
	public function getResults(): array
	{
		return $this->results;
	}

	/**
	 * @return array<int, array{tool_name: string, output: string, timestamp?: int}>
	 */
	public function toArray(): array
	{
		return array_map(
			static fn (ToolResultDto $dto): array => $dto->toArray(),
			$this->results
		);
	}

	/**
	 * @param list<ToolResultDto> $optimizedResults
	 */
	public function withOptimizedResults(array $optimizedResults): self
	{
		return new self($optimizedResults);
	}

	/**
	 * @param callable(ToolResultDto): ToolResultDto $transformer
	 */
	public function mapResults(callable $transformer): self
	{
		return new self(array_map($transformer, $this->results));
	}

	/**
	 * @param callable(ToolResultDto): bool $predicate
	 */
	public function filterResults(callable $predicate): self
	{
		return new self(array_values(array_filter($this->results, $predicate)));
	}
}
