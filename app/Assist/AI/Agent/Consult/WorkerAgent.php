<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent\Consult;

use App\Assist\AI\Agent\BaseAgent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\ToolProperty;

final class WorkerAgent extends BaseAgent
{
	public function __construct(private readonly ArchitectAgent $architect)
	{
		parent::__construct();
	}

	protected function provider(): AIProviderInterface
	{
		// @phpstan-ignore-next-line
		return resolve('ai.provider.sonnet');
	}

	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Reference/worker.md');
	}

	protected function getName(): string
	{
		return 'worker';
	}

	public function getExcludedTools(): array
	{
		return [];
	}

	public function getExcludedContexts(): array
	{
		return [
			'resource:reference://prompt-engineering',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}

	/** @return ToolInterface[] */
	protected function availableTools(): array
	{
		$tools = parent::availableTools();
		$tools[] = $this->buildConsultTool();

		return $tools;
	}

	private function buildConsultTool(): ToolInterface
	{
		$toolProperty = ToolProperty::make(
			name: 'context',
			type: PropertyType::STRING,
			description: 'Full context: what you have done, the decision you face, and why you cannot resolve it yourself.',
		);

		$toolProperty->isRequired();

		return Tool::make(
			name: 'consult_architect',
			description: (string) file_get_contents(__DIR__ . '/../Reference/consult.md'),
		)
			->addProperty($toolProperty)
			->setCallable(function (string $context): string {
				$directive = $this->architect->consult($context);

				if ('Blocked' === $directive->decision) {
					return "BLOCKED: {$directive->blockedReason}";
				}

				return "Decision: {$directive->decision} | Complexity: {$directive->complexity}\n{$directive->guidance}";
			});
	}
}
