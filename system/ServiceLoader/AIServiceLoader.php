<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
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
				model: env('OLLAMA_MODEL'),
			),
			// for more information: https://gapgpt.app/platform-v2
			'gapgpt' => new OpenAILike(
				baseUri: 'https://api.gapgpt.app/v1',
				key: env('GAPGPT_API_KEY'),
				model: env('GAPGPT_MODEL'),
			),
			'claude' => new Anthropic(
				key: env('CLAUDE_API_KEY'),
				model: env('CLAUDE_MODEL'),
				max_tokens: 1024,
			),
		];

		$embeddingProviders = [
			'ollama' => new OllamaEmbeddingsProvider(
				url: 'host.docker.internal:11434/api',
				model: env('OLLAMA_EMBEDDING_MODEL'),
			),
			// for more information: https://gapgpt.app/platform-v2
			'gapgpt' => new OpenAILikeEmbeddings(
				baseUri: 'https://api.gapgpt.app/v1',
				key: env('GAPGPT_EMBEDDING_KEY'),
				model: env('GAPGPT_EMBEDDING_MODEL'),
			),
		];

		$container->bind(AIProviderInterface::class, $llmProviders[env('MODEL_PROVIDER')]);
		$container->bind(EmbeddingsProviderInterface::class, $embeddingProviders[env('EMBEDDING_MODEL_PROVIDER')]);
	}
}
