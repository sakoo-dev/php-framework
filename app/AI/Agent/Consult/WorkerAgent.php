<?php

declare(strict_types=1);

namespace App\AI\Agent\Consult;

use App\AI\Agent\Agent;
use App\AI\Neuron\Tool\ConsultTool;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\ToolInterface;

final class WorkerAgent extends Agent
{
	public function __construct(private readonly ArchitectAgent $architect)
	{
		parent::__construct();
	}

	protected function provider(): AIProviderInterface
	{
		// @phpstan-ignore-next-line
		return resolve($this->supportsThinking() ? 'ai.gapgpt.claude.sonnet.thinking' : 'ai.gapgpt.claude.sonnet');
	}

	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../../Prompt/Role/worker.md');
	}

	public function getName(): string
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
			'skill://prompt-engineering',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}

	/** @return ToolInterface[] */
	protected function availableTools(): array
	{
		$tools = parent::availableTools();
		$tools[] = ConsultTool::make($this->architect);

		return $tools;
	}
}
