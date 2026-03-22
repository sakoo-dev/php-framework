<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use Sakoo\Framework\Core\Path\Path;

abstract class BaseAgent extends Agent
{
	protected function provider(): AIProviderInterface
	{
		return new Ollama(
			url: 'host.docker.internal:11434/api',
			model: 'qwen3-vl:4b',
		);
	}

	protected function chatHistory(): ChatHistoryInterface
	{
		$path = Path::getStorageDir() . '/ai/chat-history';

		return new FileChatHistory(
			directory: $path,
			key: 'THREAD_ID',
			contextWindow: 50000
		);
	}

	protected function tools(): array
	{
		return [
			...McpConnector::make([
				'command' => 'php',
				'args' => ['assist', 'mcp:run'],
			])->tools(),
		];
	}
}
