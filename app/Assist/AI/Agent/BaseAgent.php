<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Tools\ToolInterface;
use System\Path\Path;

abstract class BaseAgent extends Agent
{
	protected function provider(): AIProviderInterface
	{
		return resolve(AIProviderInterface::class);
	}

	protected function chatHistory(): ChatHistoryInterface
	{
		return new FileChatHistory(
			directory: Path::getStorageDir() . '/ai/chat-history',
			key: date('YmdHis'),
			contextWindow: 8000,
		);
	}

	/** @return ToolInterface[] */
	protected function mcpTools(): array
	{
		return McpConnector::make([
			'command' => 'php',
			'args' => ['assist', 'mcp:run'],
		])->tools();
	}

	/** @return ToolInterface[] */
	protected function tools(): array
	{
		return [];
	}
}
