<?php

declare(strict_types=1);

namespace App\AI\Neuron\Tool;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class SearchMcpToolsTool extends Tool
{
	/**
	 * @param array<array{name: string, description: string}> $availableTools Tools accessible by this agent
	 */
	public function __construct(private readonly array $availableTools)
	{
		parent::__construct(
			name: 'search_mcp_tools',
			description: 'Search and list MCP tools you can use. Only shows tools available to you.',
		);
	}

	protected function properties(): array
	{
		return [
			ToolProperty::make(
				name: 'query',
				description: 'Optional search query to filter tools by name or description',
				type: PropertyType::STRING,
			),
			ToolProperty::make(
				name: 'limit',
				description: 'Maximum number of results to return',
				type: PropertyType::INTEGER,
			),
		];
	}

	public function __invoke(?string $query = null, int $limit = 20): string
	{
		$query = $query ?? '';
		$tools = $this->searchTools($query);
		$tools = array_slice($tools, 0, $limit);

		return json_encode(['tools' => $tools], JSON_THROW_ON_ERROR);
	}

	/**
	 * @return list<array{name: string, description: string}>
	 */
	private function searchTools(string $query): array
	{
		$results = [];

		foreach ($this->availableTools as $tool) {
			if ('' === $query) {
				$results[] = $tool;

				continue;
			}

			$queryLower = strtolower($query);
			$nameLower = strtolower($tool['name']);
			$descLower = strtolower($tool['description']);

			if (str_contains($nameLower, $queryLower) || str_contains($descLower, $queryLower)) {
				$results[] = $tool;
			}
		}

		return $results;
	}
}
