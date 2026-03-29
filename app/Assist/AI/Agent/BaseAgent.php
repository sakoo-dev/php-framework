<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use Sakoo\Framework\Core\Path\Path;

abstract class BaseAgent extends Agent
{
	protected function provider(): AIProviderInterface
	{
		return resolve(AIProviderInterface::class);
	}

	protected function chatHistory(): ChatHistoryInterface
	{
		$path = Path::getStorageDir() . '/ai/chat-history';

		return new FileChatHistory(
			directory: $path,
			key: date('YmdHis'),
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
