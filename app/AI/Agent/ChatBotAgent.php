<?php

declare(strict_types=1);

namespace App\AI\Agent;

use App\AI\Neuron\Tool\PromptFetchTool;
use App\AI\Neuron\Tool\ResourceFetchTool;
use App\AI\Neuron\Tool\RetrievalTool;

class ChatBotAgent extends Agent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Role/chatbot.md');
	}

	public function getName(): string
	{
		return 'chatbot';
	}

	protected function includedTools(): array
	{
		return [
			...$this->mcpTools()->only([])->tools(),
			ResourceFetchTool::make($this->mcpElementsClass()),
			PromptFetchTool::make($this->mcpElementsClass()),
			new RetrievalTool($this),
		];
	}

	protected function contexts(): array
	{
		return [
			'project://structure',
			'project://info',
			'project://commands',
			'skill://sakoo-identity',
		];
	}
}
