<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use App\Assist\AI\Neuron\ChatHistory;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use NeuronAI\Tools\ToolInterface;
use NeuronAI\Tools\Toolkits\Calculator\CalculatorToolkit;
use NeuronAI\Tools\Toolkits\Calendar\CalendarToolkit;
use NeuronAI\Tools\Toolkits\FileSystem\FileSystemToolkit;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

abstract class BaseAgent extends RAG
{
	abstract protected function getName(): string;

	protected function provider(): AIProviderInterface
	{
		return resolve(AIProviderInterface::class);
	}

	protected function embeddings(): EmbeddingsProviderInterface
	{
		return resolve(EmbeddingsProviderInterface::class);
	}

	protected function vectorStore(): VectorStoreInterface
	{
		$file = File::open(Disk::Local, Path::getStorageDir() . '/ai/embeddings/' . $this->getName() . '.store');
		$file->create();

		return new FileVectorStore(directory: $file->parentDir(), name: $this->getName());
	}

	protected function chatHistory(): ChatHistoryInterface
	{
		return new ChatHistory(
			directory: Path::getStorageDir() . '/ai/chat-history',
			key: $this->getName(),
			contextWindow: 50000,
		);
	}

	/** @return ToolInterface[] */
	protected function tools(): array
	{
		return [
			...FileSystemToolkit::make()->tools(),
			...CalculatorToolkit::make()->tools(),
			...CalendarToolkit::make()->tools(),
			...McpConnector::make(['command' => 'php', 'args' => ['assist', 'mcp:run']])->tools(),
		];
	}
}
