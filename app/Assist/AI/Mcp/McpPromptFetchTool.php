<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Formatter\PromptResultFormatter;
use Mcp\Schema\Content\TextContent;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;

/**
 * NeuronAI tool that fetches a zero-argument MCP Prompt by name on demand.
 *
 * Only zero-arg prompts can be fetched lazily — they act as static reference
 * content (system context blocks, guidelines, etc.).
 * Parameterised prompts (e.g. dev_task, review_file) require runtime arguments
 * and remain invoked directly via their MCP tool calls.
 */
final class McpPromptFetchTool
{
	/** @param class-string $sourceClass Class whose methods carry #[McpPrompt] attributes */
	public static function make(string $sourceClass): ToolInterface
	{
		$property = ToolProperty::make(
			name: 'name',
			type: PropertyType::STRING,
			description: 'The exact name of the MCP Prompt to fetch (as listed in the MCP Index).',
		);

		$property->isRequired();

		return Tool::make(
			name: 'get_mcp_prompt',
			description: 'Fetch the full content of a zero-argument MCP Prompt by its name. Use the MCP Index in the system prompt to discover available prompt names.',
		)
			->addProperty($property)
			->setCallable(fn (string $name): string => self::resolve($sourceClass, $name));
	}

	/** @param class-string $sourceClass */
	private static function resolve(string $sourceClass, string $name): string
	{
		/** @var \ReflectionClass<object> $reflectionClass */
		$reflectionClass = new \ReflectionClass($sourceClass);

		foreach ($reflectionClass->getMethods() as $method) {
			if (0 !== $method->getNumberOfParameters()) {
				continue;
			}

			foreach ($method->getAttributes(McpPrompt::class) as $attribute) {
				/** @var McpPrompt $attr */
				$attr = $attribute->newInstance();
				$promptName = $attr->name ?? $method->getName();

				if ($promptName !== $name) {
					continue;
				}

				$result = $method->invoke($reflectionClass->newInstance());
				$formatter = new PromptResultFormatter();
				$output = '';

				foreach ($formatter->format($result) as $message) {
					if (is_subclass_of($message->content, TextContent::class)) {
						/** @var string $extracted */
						$extracted = $message->content->text;
						$output .= "[Prompt:{$name}|{$message->role->value}]\n$extracted\n";
					}
				}

				return '' !== $output ? $output : "[Prompt:{$name}] (empty)";
			}
		}

		return "[Prompt:{$name}] not found. Available prompt names are listed in the MCP Index.";
	}
}
