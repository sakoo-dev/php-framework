<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent\Consult;

use App\Assist\AI\Agent\BaseAgent;
use App\Assist\AI\Mcp\McpConsultTool;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\ToolInterface;

final class WorkerAgent extends BaseAgent
{
	public function __construct(private readonly ArchitectAgent $architect)
	{
		parent::__construct();
	}

	protected function provider(): AIProviderInterface
	{
		// @phpstan-ignore-next-line
		return resolve($this->supportsThinking() ? 'ai.provider.sonnet.thinking' : 'ai.provider.sonnet');
	}

	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../../Prompt/Reference/worker.md');
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
			'reference://prompt-engineering',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}

	/** @return ToolInterface[] */
	protected function availableTools(): array
	{
		$tools = parent::availableTools();
		$tools[] = McpConsultTool::make($this->architect);

		return $tools;
	}
}
