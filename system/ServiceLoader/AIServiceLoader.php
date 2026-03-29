<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAILike;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OpenAILikeEmbeddings;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\ServiceLoader\ServiceLoader;

class AIServiceLoader extends ServiceLoader
{
	public function load(Container $container): void
	{
		$llmProviders = [
			'ollama' => new Ollama(
				url: 'host.docker.internal:11434/api',
				model: 'qwen3-vl:4b',
			),
			'gapgpt' => new OpenAILike(
				baseUri: 'https://api.gapgpt.app/v1',
				key: 'GAP_GPT_KEY',
				model: 'gapgpt-qwen-3.5',
			),
		];

		$embeddingProviders = [
			'ollama' => new OllamaEmbeddingsProvider(
				url: 'host.docker.internal:11434/api',
				model: 'qwen3-embedding:8b',
			),
			'gapgpt' => new OpenAILikeEmbeddings(
				baseUri: 'https://api.gapgpt.app/v1',
				key: 'GAP_GPT_KEY',
				model: 'gapgpt-qwen-3.5',
			),
		];

		$container->bind(AIProviderInterface::class, $llmProviders['ollama']);
		$container->bind(EmbeddingsProviderInterface::class, $embeddingProviders['ollama']);
	}
}
