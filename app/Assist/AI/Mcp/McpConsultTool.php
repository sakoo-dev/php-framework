<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use App\Assist\AI\Agent\Consult\ArchitectAgent;
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
final class McpConsultTool
{
	public static function make(ArchitectAgent $architect): ToolInterface
	{
		$toolProperty = ToolProperty::make(
			name: 'context',
			type: PropertyType::STRING,
			description: 'Full context: what you have done, the decision you face, and why you cannot resolve it yourself.',
		);

		$toolProperty->isRequired();

		return Tool::make(
			name: 'consult_architect',
			description: (string) file_get_contents(__DIR__ . '/../Prompt/Reference/consult.md'),
		)
			->addProperty($toolProperty)
			->setCallable(function (string $context) use ($architect): string {
				$directive = $architect->consult($context);

				if ('Blocked' === $directive->decision) {
					return "BLOCKED: {$directive->blockedReason}";
				}

				return "Decision: {$directive->decision} | Complexity: {$directive->complexity}\n{$directive->guidance}";
			});
	}
}
