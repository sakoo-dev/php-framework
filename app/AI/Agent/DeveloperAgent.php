<?php

declare(strict_types=1);

namespace App\AI\Agent;

use NeuronAI\RAG\PostProcessor\LocalAIRerankerPostProcessor;
use Sakoo\AI\Agent\Agent;
use Sakoo\AI\Neuron\Tool\PromptFetchTool;
use Sakoo\AI\Neuron\Tool\ResourceFetchTool;
use Sakoo\AI\Neuron\Tool\RetrievalTool;

class DeveloperAgent extends Agent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Role/software-engineer.md');
	}

	public function getName(): string
	{
		return 'developer';
	}

	protected function includedTools(): array
	{
		return [
			...$this->fileSystemTools(),
			...$this->calculatorTools(),
			...$this->calendarTools(),
			...$this->mcpTools()->exclude([])->tools(),
			ResourceFetchTool::make($this->mcpElementsClass(), container()),
			PromptFetchTool::make($this->mcpElementsClass(), container()),
			new RetrievalTool($this, new LocalAIRerankerPostProcessor('ai.reranker')),
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
