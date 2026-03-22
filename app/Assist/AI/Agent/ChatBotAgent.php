<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\SystemPrompt;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use System\Path\Path;

class ChatBotAgent extends RAG
{
	protected function provider(): AIProviderInterface
	{
		return new Ollama(
			url: 'host.docker.internal:11434/api',
			model: 'qwen3-vl:4b',
		);
	}

	protected function embeddings(): EmbeddingsProviderInterface
	{
		return new OllamaEmbeddingsProvider(
			url: 'host.docker.internal:11434/api',
			model: 'qwen3-embedding:8b',
		);
	}

	protected function vectorStore(): VectorStoreInterface
	{
		$path = Path::getStorageDir() . '/ai/embeddings';

		return new FileVectorStore(
			directory: $path,
			name: 'chatbot'
		);
	}

	protected function instructions(): string
	{
		return (string) new SystemPrompt(
			background: [
				file_get_contents(__DIR__ . '/../Prompt/chatbot-agent-prompt.md'),
			],
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
