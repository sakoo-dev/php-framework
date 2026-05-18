<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use App\Assist\AI\Agent\Consult\ArchitectAgent;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;

/**
 * NeuronAI tool that delegates a blocking architectural decision to the ArchitectAgent.
 *
 * When a worker agent reaches a decision point it cannot resolve itself — a
 * design trade-off, a pattern choice, a structural ambiguity — it invokes this
 * tool with full context. The ArchitectAgent returns a structured directive
 * (decision, complexity, guidance, or a BLOCKED signal) that the worker uses
 * to continue or halt its task.
 *
 * @see ArchitectAgent  The agent that processes the consult request.
 * @see McpConsultTool  Prompt reference loaded from Reference/consult.md.
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
