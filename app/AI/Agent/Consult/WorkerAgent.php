<?php

declare(strict_types=1);

namespace App\AI\Agent\Consult;

use App\AI\Agent\Agent;
use App\AI\Neuron\Tool\ConsultTool;
use App\AI\Neuron\Tool\PromptFetchTool;
use App\AI\Neuron\Tool\ResourceFetchTool;
use App\AI\Neuron\Tool\RetrievalTool;
use NeuronAI\Providers\AIProviderInterface;

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

	protected function includedTools(): array
	{
		return [
			...$this->fileSystemTools(),
			...$this->calculatorTools(),
			...$this->calendarTools(),
			...$this->mcpTools()->exclude([])->tools(),
			ResourceFetchTool::make($this->mcpElementsClass()),
			PromptFetchTool::make($this->mcpElementsClass()),
			new RetrievalTool($this),
			ConsultTool::make($this->architect),
		];
	}

	protected function contexts(): array
	{
		return [
			'file://list',
			'prompt://system',
			'project://structure',
			'project://info',
			'project://makefile',
			'project://commands',
			'skill://architecture',
			'skill://conventions',
			'skill://sakoo-identity',
			'skill://quality-assurance',
			'skill://file-handling',
			'skill://security-checklist',
		];
	}
}
