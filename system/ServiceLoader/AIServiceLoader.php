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
use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\ServiceLoader\ServiceLoader;

class AIServiceLoader extends ServiceLoader
{
	public function load(Container $container): void
	{
		$llmProviders = [
			'ollama' => new Ollama(
				url: 'host.docker.internal:11434/api',
				model: Env::get('OLLAMA_MODEL', ''),
				parameters: ['num_ctx' => 64 * 1024],
			),
			// for more information: https://gapgpt.app/platform-v2
			'gapgpt' => new OpenAILike(
				baseUri: 'https://api.gapgpt.app/v1',
				key: Env::get('GAPGPT_API_KEY', ''),
				model: Env::get('GAPGPT_MODEL', ''),
			),
			'claude' => new Anthropic(
				key: Env::get('CLAUDE_API_KEY', ''),
				model: Env::get('CLAUDE_MODEL', ''),
				max_tokens: 1024,
			),
		];

		$embeddingProviders = [
			'ollama' => new OllamaEmbeddingsProvider(
				url: 'host.docker.internal:11434/api',
				model: Env::get('OLLAMA_EMBEDDING_MODEL', ''),
			),
			// for more information: https://gapgpt.app/platform-v2
			'gapgpt' => new OpenAILikeEmbeddings(
				baseUri: 'https://api.gapgpt.app/v1',
				key: Env::get('GAPGPT_EMBEDDING_KEY', ''),
				model: Env::get('GAPGPT_EMBEDDING_MODEL', ''),
			),
		];

		$container->bind(AIProviderInterface::class, $llmProviders[Env::get('MODEL_PROVIDER', 'ollama')]);
		$container->bind(EmbeddingsProviderInterface::class, $embeddingProviders[Env::get('EMBEDDING_MODEL_PROVIDER', 'ollama')]);

		$container->bind('ai.provider.sonnet', new OpenAILike(
			baseUri: 'https://api.gapgpt.app/v1',
			key: Env::get('GAPGPT_API_KEY', ''),
			model: Env::get('CLAUDE_SONNET_MODEL', 'claude-sonnet-4-5'),
		));

		$container->bind('ai.provider.opus', new OpenAILike(
			baseUri: 'https://api.gapgpt.app/v1',
			key: Env::get('GAPGPT_API_KEY', ''),
			model: Env::get('CLAUDE_OPUS_MODEL', 'claude-opus-4-5'),
		));
	}
}
