<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use App\Assist\AI\Mcp\McpTokenCalculator;
use App\Assist\AI\Mcp\McpTokenObserver;
use App\Assist\AI\Mcp\McpTokenStorageInterface;
use App\Assist\AI\Mcp\Storage\JsonlMcpTokenStorage;
use App\Assist\AI\Metric\JsonlMetricStorage;
use App\Assist\AI\Metric\MetricStorageInterface;
use App\Assist\AI\Metric\NullQualityEvaluator;
use App\Assist\AI\Metric\QualityEvaluatorInterface;
use App\Assist\AI\Neuron\Cache\CacheStorageInterface;
use App\Assist\AI\Neuron\Cache\FileCacheStorage;
use App\Assist\AI\Neuron\CircuitBreaker\CircuitBreakerStorageInterface;
use App\Assist\AI\Neuron\CircuitBreaker\FileCircuitBreakerStorage;
use App\Assist\AI\Neuron\HighAvailableProviderBuilder;
use App\Assist\AI\Neuron\Http\LoggingHttpClient;
use App\Assist\AI\Neuron\Model\Anthropic\Claude;
use App\Assist\AI\Neuron\Provider\GapGpt;
use App\Assist\AI\Neuron\Provider\GapGptEmbedding;
use App\Assist\AI\Neuron\Retry\RetryPolicy;
use App\Assist\AI\Neuron\Throttle\FileThrottleStorage;
use App\Assist\AI\Neuron\Throttle\ThrottleStorageInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use Psr\Clock\ClockInterface;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\Logger\FileLogger;
use Sakoo\Framework\Core\ServiceLoader\ServiceLoader;
use System\Path\Path;

class AIServiceLoader extends ServiceLoader
{
	public function load(Container $container): void
	{
		$this->registerAvailability($container);
		$this->registerMetrics($container);
		$this->registerProviders($container);
		$this->registerEmbeddings($container);
		$this->registerMcp($container);
	}

	private function registerAvailability(Container $container): void
	{
		$container->singleton(CircuitBreakerStorageInterface::class, new FileCircuitBreakerStorage(failureThreshold: 5, openWindowSeconds: 60));
		$container->singleton(CacheStorageInterface::class, new FileCacheStorage());
		$container->singleton(ThrottleStorageInterface::class, new FileThrottleStorage());
	}

	private function registerProviders(Container $container): void
	{
		$httpClient = new LoggingHttpClient($container->resolve('logger.ai'));

		$container->bind('ai.gapgpt.claude.haiku', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::HAIKU_3_5, httpClient: $httpClient)
		));

		$container->bind('ai.gapgpt.claude.sonnet', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::SONNET_4_6, httpClient: $httpClient)
		));

		$container->bind('ai.gapgpt.claude.sonnet.thinking', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::SONNET_4_6, extendedThinking: true, httpClient: $httpClient)
		));

		$container->bind('ai.gapgpt.claude.opus', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::OPUS_4_7, httpClient: $httpClient)
		));

		$container->bind('ai.gapgpt.claude.opus.thinking', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::OPUS_4_7, extendedThinking: true, httpClient: $httpClient)
		));

		$container->bind('ai.ollama.qwen3.4b', new Ollama(
			url: 'host.docker.internal:11434/api',
			model: 'qwen3-vl:4b',
			parameters: ['num_ctx' => 64 * 1024],
		));

		$primaryProviderKey = Env::get('PRIMARY_AI_PROVIDER', 'ai.gapgpt.claude.sonnet');
		$primaryProvider = $container->resolve($primaryProviderKey);
		$container->bind(AIProviderInterface::class, $primaryProvider);

		$container->bind(
			AIProviderInterface::class,
			HighAvailableProviderBuilder::wrap($primaryProvider)
				->withRetry(new RetryPolicy(maxAttempts: 3, baseDelayMs: 200, multiplier: 2.0))
				->withCircuitBreaker($container->resolve(CircuitBreakerStorageInterface::class), $primaryProviderKey)
				->withCache($container->resolve(CacheStorageInterface::class), $primaryProviderKey)
				->build()
		);
	}

	private function registerEmbeddings(Container $container): void
	{
		$container->bind('ai.gapgpt.qwen3-5.embedding', new GapGptEmbedding(
			model: 'gapgpt-qwen-3.5',
		));

		$container->bind('ai.ollama.qwen3.8b.embedding', new OllamaEmbeddingsProvider(
			url: 'host.docker.internal:11434/api',
			model: 'qwen3-embedding:8b',
		));

		$embeddingProviderKey = Env::get('EMBEDDING_AI_PROVIDER', 'ai.gapgpt.qwen3-5.embedding');
		$container->bind(EmbeddingsProviderInterface::class, $container->resolve($embeddingProviderKey));
	}

	private function registerMetrics(Container $container): void
	{
		$container->singleton('logger.ai', new FileLogger($container->resolve(ClockInterface::class), Path::getStorageDir() . '/ai/logs'));
		$container->singleton(MetricStorageInterface::class, new JsonlMetricStorage());
		$container->singleton(QualityEvaluatorInterface::class, new NullQualityEvaluator());
	}

	private function registerMcp(Container $container): void
	{
		$mcpStorage = new JsonlMcpTokenStorage();
		$container->singleton(McpTokenStorageInterface::class, $mcpStorage);
		$container->singleton(McpTokenObserver::class, new McpTokenObserver(calculator: $container->resolve(McpTokenCalculator::class), storage: $mcpStorage));
	}
}
