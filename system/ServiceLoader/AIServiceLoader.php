<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\PostProcessor\LocalAIRerankerPostProcessor;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Sakoo\AI\Mcp\McpTokenCalculator;
use Sakoo\AI\Mcp\McpTokenJsonlStorage;
use Sakoo\AI\Mcp\McpTokenObserver;
use Sakoo\AI\Mcp\McpTokenStorageInterface;
use Sakoo\AI\Mcp\McpWebClient;
use Sakoo\AI\Mcp\ProjectContextInterface;
use Sakoo\AI\Neuron\Cache\CacheStorageInterface;
use Sakoo\AI\Neuron\Cache\FileCacheStorage;
use Sakoo\AI\Neuron\CircuitBreaker\CircuitBreakerStorageInterface;
use Sakoo\AI\Neuron\CircuitBreaker\FileCircuitBreakerStorage;
use Sakoo\AI\Neuron\FileSystem\FileStorageInterface;
use Sakoo\AI\Neuron\Guard\AuditStorageInterface;
use Sakoo\AI\Neuron\Guard\Dataset\IllegalPatternDataset;
use Sakoo\AI\Neuron\Guard\Detector\IllegalContentDetector;
use Sakoo\AI\Neuron\Guard\Detector\LLMModerationDetector;
use Sakoo\AI\Neuron\Guard\Detector\PiiMaskingDetector;
use Sakoo\AI\Neuron\Guard\GuardrailPipeline;
use Sakoo\AI\Neuron\Guard\JsonlAuditStorage;
use Sakoo\AI\Neuron\HighAvailableProviderBuilder;
use Sakoo\AI\Neuron\Http\LoggingHttpClient;
use Sakoo\AI\Neuron\Metric\JsonlMetricStorage;
use Sakoo\AI\Neuron\Metric\MetricStorageInterface;
use Sakoo\AI\Neuron\Metric\NullQualityEvaluator;
use Sakoo\AI\Neuron\Metric\QualityEvaluatorInterface;
use Sakoo\AI\Neuron\Model\Anthropic\Claude;
use Sakoo\AI\Neuron\Optimizer\PromptOptimizer;
use Sakoo\AI\Neuron\Optimizer\PromptOptimizerInterface;
use Sakoo\AI\Neuron\Provider\AvalAI;
use Sakoo\AI\Neuron\Provider\GapGpt;
use Sakoo\AI\Neuron\Provider\GapGptEmbedding;
use Sakoo\AI\Neuron\Provider\OpenAIModeration;
use Sakoo\AI\Neuron\Retry\RetryPolicy;
use Sakoo\AI\Neuron\Throttle\FileThrottleStorage;
use Sakoo\AI\Neuron\Throttle\ThrottleStorageInterface;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\Http\Client\HttpClient;
use Sakoo\Framework\Core\Http\HttpFactory;
use Sakoo\Framework\Core\Logger\FileLogger;
use Sakoo\Framework\Core\ServiceLoader\ServiceLoader;
use System\AI\HttpClientBridge;
use System\AI\ProjectContext;
use System\AI\SakooFileStorage;
use System\Path\Path;

class AIServiceLoader extends ServiceLoader
{
	public function load(Container $container): void
	{
		$this->registerCore($container);
		$this->registerOptimizers($container);
		$this->registerMetrics($container);
		$this->registerAvailability($container);
		$this->registerGuard($container);
		$this->registerProviders($container);
		$this->registerEmbeddings($container);
		$this->registerMcp($container);
		$this->neuronProcessors($container);
	}

	/**
	 * Binds the two framework-agnostic interfaces to their Sakoo implementations.
	 * When shipping app/AI as a standalone package, replace these bindings with
	 * framework-specific adapters (e.g. LaravelFileStorage, LaravelProjectContext).
	 */
	private function registerCore(Container $container): void
	{
		$container->singleton(FileStorageInterface::class, new SakooFileStorage());
		$container->singleton(ProjectContextInterface::class, new ProjectContext());

		$factory = new HttpFactory();
		$container->singleton(RequestFactoryInterface::class, $factory);
		$container->singleton(McpWebClient::class, new McpWebClient(
			new HttpClientBridge($container->resolve(HttpClient::class)),
			$factory,
		));
	}

	private function registerAvailability(Container $container): void
	{
		$storageDir = (string) Path::getStorageDir();
		$fileStorage = $container->resolve(FileStorageInterface::class);
		$container->singleton(CircuitBreakerStorageInterface::class, new FileCircuitBreakerStorage($fileStorage, $storageDir, failureThreshold: 5, openWindowSeconds: 60));
		$container->singleton(CacheStorageInterface::class, new FileCacheStorage($fileStorage, $storageDir));
		$container->singleton(ThrottleStorageInterface::class, new FileThrottleStorage($fileStorage, $storageDir));
		$container->bind(LoggingHttpClient::class, fn () => new LoggingHttpClient($container->resolve('logger.ai')));
	}

	private function registerOptimizers(Container $container): void
	{
		$container->singleton(PromptOptimizerInterface::class, new PromptOptimizer());
		$container->singleton(McpTokenCalculator::class, new McpTokenCalculator());
	}

	private function registerGuard(Container $container): void
	{
		$storageDir = (string) Path::getStorageDir();
		$fileStorage = $container->resolve(FileStorageInterface::class);
		$httpClient = $container->resolve(LoggingHttpClient::class);

		$container->bind('moderation.avalai.openai', new LLMModerationDetector(
			moderation: new OpenAIModeration(
				baseUri: 'https://api.avalai.ir/v1',
				// @phpstan-ignore cast.string
				apiKey: ((string) Env::get('AVALAI_API_KEY', '')),
				modelName: OpenAIModeration::MODERATION_OMNI_LATEST,
				httpClient: $httpClient,
			),
		));

		$container->singleton(GuardrailPipeline::class, new GuardrailPipeline([
			new PiiMaskingDetector(),
			new IllegalContentDetector(IllegalPatternDataset::all()),
			resolve('moderation.avalai.openai'),
		]));

		$container->singleton(
			AuditStorageInterface::class,
			new JsonlAuditStorage($fileStorage, $storageDir),
		);
	}

	private function registerProviders(Container $container): void
	{
		$container->bind('ai.gapgpt.claude.haiku', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::HAIKU_3_5, httpClient: $container->resolve(LoggingHttpClient::class))
		));

		$container->bind('ai.gapgpt.claude.sonnet', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::SONNET_4_6, httpClient: $container->resolve(LoggingHttpClient::class))
		));

		$container->bind('ai.gapgpt.claude.sonnet.thinking', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::SONNET_4_6, extendedThinking: true, httpClient: $container->resolve(LoggingHttpClient::class))
		));

		$container->bind('ai.gapgpt.claude.opus', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::OPUS_4_7, httpClient: $container->resolve(LoggingHttpClient::class))
		));

		$container->bind('ai.gapgpt.claude.opus.thinking', GapGpt::withAIModelObject(
			new Claude(modelName: Claude::OPUS_4_7, extendedThinking: true, httpClient: $container->resolve(LoggingHttpClient::class))
		));

		$container->bind('ai.avalai.claude.sonnet', AvalAI::withAIModelObject(
			new Claude(modelName: Claude::SONNET_4_6, httpClient: $container->resolve(LoggingHttpClient::class))
		));

		$container->bind('ai.ollama.qwen3.4b', new Ollama(
			url: 'host.docker.internal:11434/api',
			model: 'qwen3-vl:4b',
			parameters: ['num_ctx' => 64 * 1024],
		));

		$primaryProviderKey = Env::get('PRIMARY_AI_PROVIDER', 'ai.gapgpt.claude.sonnet');

		$container->bind(
			AIProviderInterface::class,
			HighAvailableProviderBuilder::wrap($container->resolve($primaryProviderKey))
				->withRetry(new RetryPolicy(maxAttempts: 3, baseDelayMs: 200, multiplier: 2.0))
				->withCircuitBreaker($container->resolve(CircuitBreakerStorageInterface::class), $primaryProviderKey)
				->withCache($container->resolve(CacheStorageInterface::class), $primaryProviderKey)
				->withFallbacks([
					resolve(Env::get('SECONDARY_AI_PROVIDER', 'ai.avalai.claude.sonnet')),
					resolve(Env::get('TERTIARY_AI_PROVIDER', 'ai.ollama.qwen3.4b')),
				])->build()
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
		$storageDir = (string) Path::getStorageDir();
		$fs = $container->resolve(FileStorageInterface::class);

		$container->singleton('logger.ai', new FileLogger($container->resolve(ClockInterface::class), $storageDir . '/ai/logs'));
		$container->singleton(MetricStorageInterface::class, new JsonlMetricStorage($fs, $storageDir));
		$container->singleton(QualityEvaluatorInterface::class, new NullQualityEvaluator());
	}

	private function registerMcp(Container $container): void
	{
		$storageDir = (string) Path::getStorageDir();
		$fs = $container->resolve(FileStorageInterface::class);

		$mcpStorage = new McpTokenJsonlStorage($fs, $storageDir);
		$container->singleton(McpTokenStorageInterface::class, $mcpStorage);
		$container->singleton(McpTokenObserver::class, new McpTokenObserver(
			calculator: $container->resolve(McpTokenCalculator::class),
			storage: $mcpStorage,
		));
	}

	private function neuronProcessors(Container $container): void
	{
		$container->singleton('ai.reranker', new LocalAIRerankerPostProcessor(
			host: '',
			key: '',
			model: '',
			topN: 8,
		));
	}
}
