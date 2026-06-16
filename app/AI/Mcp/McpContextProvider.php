<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;

/**
 * Context provider that injects only a compact MCP index into the system prompt.
 */
final class McpContextProvider
{
	/** @param class-string $sourceClass Class whose methods carry #[McpResource] / #[McpPrompt] attributes */
	public function __construct(private string $sourceClass) {}

	/**
	 * Returns a single-element array containing the compact index block (~400 tokens).
	 * This replaces the previous ~45 k token payload of the eager provider.
	 *
	 * @param string[] $included URIs / prompt names to add to index
	 *
	 * @return string[]
	 */
	public function resolve(array $included): array
	{
		$entries = $this->scanEntries($this->sourceClass, $included);

		if (!$entries) {
			return [];
		}

		$lines = ['[MCP Index — fetch full content via get_mcp_resource or get_mcp_prompt tools]'];

		foreach ($entries as $entry) {
			$lines[] = sprintf('%-8s | %-40s | %s', $entry['type'], $entry['id'], $entry['description']);
		}

		return [implode(PHP_EOL, $lines)];
	}

	/**
	 * Scan $sourceClass methods for #[McpResource] and zero-arg #[McpPrompt] attributes
	 * and return a flat list of compact index entries.
	 *
	 * @param class-string $sourceClass
	 * @param string[] $included
	 *
	 * @return list<array{type: 'prompt'|'resource', id: string, description: string}>
	 */
	private function scanEntries(string $sourceClass, array $included): array
	{
		$entries = [];

		/** @var \ReflectionClass<object> $reflectionClass */
		$reflectionClass = new \ReflectionClass($sourceClass);

		foreach ($reflectionClass->getMethods() as $method) {
			foreach ($method->getAttributes() as $attribute) {
				$attrName = $attribute->getName();

				if (McpResource::class === $attrName) {
					/** @var McpResource $attr */
					$attr = $attribute->newInstance();

					if (!in_array($attr->uri, $included)) {
						continue;
					}

					$entries[] = [
						'type' => 'resource',
						'id' => $attr->uri,
						'description' => $attr->description ?? '',
					];
				}

				if (McpPrompt::class === $attrName) {
					if (0 !== $method->getNumberOfParameters()) {
						continue;
					}

					/** @var McpPrompt $attr */
					$attr = $attribute->newInstance();

					if (!in_array($attr->name, $included)) {
						continue;
					}

					$entries[] = [
						'type' => 'prompt',
						'id' => $attr->name ?? '',
						'description' => $attr->description ?? '',
					];
				}
			}
		}

		return $entries;
	}
}
