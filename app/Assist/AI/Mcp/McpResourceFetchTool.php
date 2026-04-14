<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use App\Assist\AI\Neuron\McpContextProvider;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Formatter\ResourceResultFormatter;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;

/**
 * NeuronAI tool that fetches a single MCP Resource by URI on demand.
 *
 * This is the pull-side counterpart to the lightweight index injected into the
 * system prompt by {@see McpContextProvider}.
 * Instead of embedding all resource content up-front (~45 k tokens), the agent
 * calls this tool only when it actually needs a specific resource.
 */
final class McpResourceFetchTool
{
	/** @param class-string $sourceClass Class whose methods carry #[McpResource] attributes */
	public static function make(string $sourceClass): ToolInterface
	{
		$property = ToolProperty::make(
			name: 'uri',
			type: PropertyType::STRING,
			description: 'The exact URI of the MCP Resource to fetch (e.g. "project://structure", "reference://conventions").',
		);

		$property->isRequired();

		return Tool::make(
			name: 'get_mcp_resource',
			description: 'Fetch the full content of an MCP Resource by its URI. Use the MCP Index in the system prompt to discover available URIs.',
		)
			->addProperty($property)
			->setCallable(fn (string $uri): string => self::resolve($sourceClass, $uri));
	}

	/** @param class-string $sourceClass */
	private static function resolve(string $sourceClass, string $uri): string
	{
		/** @var \ReflectionClass<object> $reflectionClass */
		$reflectionClass = new \ReflectionClass($sourceClass);

		foreach ($reflectionClass->getMethods() as $method) {
			foreach ($method->getAttributes(McpResource::class) as $attribute) {
				/** @var McpResource $attr */
				$attr = $attribute->newInstance();

				if ($attr->uri !== $uri) {
					continue;
				}

				$result = $method->invoke($reflectionClass->newInstance());
				$formatter = new ResourceResultFormatter();
				$output = '';

				foreach ($formatter->format($result, $uri) as $mcpResource) {
					$encoded = json_encode($mcpResource->jsonSerialize());
					$output .= "[Resource:{$uri}]\n{$encoded}";
				}

				return '' !== $output ? $output : "[Resource:{$uri}] (empty)";
			}
		}

		return "[Resource:{$uri}] not found. Available URIs are listed in the MCP Index.";
	}
}
