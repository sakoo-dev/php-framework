<?php

declare(strict_types=1);

namespace App\AI\Agent;

use NeuronAI\RAG\PostProcessor\LocalAIRerankerPostProcessor;
use Sakoo\AI\Agent\Agent;
use Sakoo\AI\Neuron\Tool\PromptFetchTool;
use Sakoo\AI\Neuron\Tool\ResourceFetchTool;
use Sakoo\AI\Neuron\Tool\RetrievalTool;

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
			ResourceFetchTool::make($this->mcpElementsClass(), container()),
			PromptFetchTool::make($this->mcpElementsClass(), container()),
			new RetrievalTool($this, new LocalAIRerankerPostProcessor('ai.reranker')),
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
