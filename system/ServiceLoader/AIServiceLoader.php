<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use App\Assist\AI\Mcp\McpTokenObserver;
use NeuronAI\HttpClient\GuzzleHttpClient;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAILike;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OpenAILikeEmbeddings;
use Psr\Clock\ClockInterface;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\Logger\FileLogger;
use Sakoo\Framework\Core\ServiceLoader\ServiceLoader;
use System\Path\Path;

class AIServiceLoader extends ServiceLoader
{
	private const GAPGPT_BASE_URI = 'https://api.gapgpt.app/v1';
	private const THINKING_PARAMETERS = ['thinking' => ['type' => 'enabled', 'budget_tokens' => 10000]];
	private const THINKING_HEADERS = ['anthropic-beta' => 'interleaved-thinking-2025-05-14'];

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
				baseUri: self::GAPGPT_BASE_URI,
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
				baseUri: self::GAPGPT_BASE_URI,
				key: Env::get('GAPGPT_EMBEDDING_KEY', ''),
				model: Env::get('GAPGPT_EMBEDDING_MODEL', ''),
			),
		];

		$container->bind(AIProviderInterface::class, $llmProviders[Env::get('MODEL_PROVIDER', 'ollama')]);
		$container->bind(EmbeddingsProviderInterface::class, $embeddingProviders[Env::get('EMBEDDING_MODEL_PROVIDER', 'ollama')]);

		$sonnetModel = Env::get('CLAUDE_SONNET_MODEL', 'claude-sonnet-4-6');
		$opusModel = Env::get('CLAUDE_OPUS_MODEL', 'claude-opus-4-6');

		$container->bind('ai.provider.sonnet', $this->buildClaudeProvider($sonnetModel, thinking: false));
		$container->bind('ai.provider.sonnet.thinking', $this->buildClaudeProvider($sonnetModel, thinking: true));
		$container->bind('ai.provider.opus', $this->buildClaudeProvider($opusModel, thinking: false));
		$container->bind('ai.provider.opus.thinking', $this->buildClaudeProvider($opusModel, thinking: true));

		$container->singleton(McpTokenObserver::class, McpTokenObserver::class);
		$container->singleton('logger.ai', new FileLogger(resolve(ClockInterface::class), Path::getStorageDir() . '/ai/logs'));
	}

	private function buildClaudeProvider(string $model, bool $thinking): OpenAILike
	{
		return new OpenAILike(
			baseUri: self::GAPGPT_BASE_URI,
			key: Env::get('GAPGPT_API_KEY', ''),
			model: $model,
			parameters: $thinking ? self::THINKING_PARAMETERS : [],
			httpClient: $thinking ? new GuzzleHttpClient(self::THINKING_HEADERS) : null,
		);
	}
}
