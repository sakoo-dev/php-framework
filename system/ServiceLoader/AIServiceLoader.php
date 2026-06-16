<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use App\AI\Mcp\McpTokenCalculator;
use App\AI\Mcp\McpTokenJsonlStorage;
use App\AI\Mcp\McpTokenObserver;
use App\AI\Mcp\McpTokenStorageInterface;
use App\AI\Neuron\Cache\CacheStorageInterface;
use App\AI\Neuron\Cache\FileCacheStorage;
use App\AI\Neuron\CircuitBreaker\CircuitBreakerStorageInterface;
use App\AI\Neuron\CircuitBreaker\FileCircuitBreakerStorage;
use App\AI\Neuron\Guard\AuditStorageInterface;
use App\AI\Neuron\Guard\Dataset\IllegalPatternDataset;
use App\AI\Neuron\Guard\Detector\IllegalContentDetector;
use App\AI\Neuron\Guard\Detector\LLMModerationDetector;
use App\AI\Neuron\Guard\Detector\PiiMaskingDetector;
use App\AI\Neuron\Guard\GuardrailPipeline;
use App\AI\Neuron\Guard\JsonlAuditStorage;
use App\AI\Neuron\HighAvailableProviderBuilder;
use App\AI\Neuron\Http\LoggingHttpClient;
use App\AI\Neuron\Metric\JsonlMetricStorage;
use App\AI\Neuron\Metric\MetricStorageInterface;
use App\AI\Neuron\Metric\NullQualityEvaluator;
use App\AI\Neuron\Metric\QualityEvaluatorInterface;
use App\AI\Neuron\Model\Anthropic\Claude;
use App\AI\Neuron\Optimizer\PromptOptimizer;
use App\AI\Neuron\Optimizer\PromptOptimizerInterface;
use App\AI\Neuron\Provider\AvalAI;
use App\AI\Neuron\Provider\GapGpt;
use App\AI\Neuron\Provider\GapGptEmbedding;
use App\AI\Neuron\Provider\OpenAIModeration;
use App\AI\Neuron\Retry\RetryPolicy;
use App\AI\Neuron\Throttle\FileThrottleStorage;
use App\AI\Neuron\Throttle\ThrottleStorageInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\PostProcessor\LocalAIRerankerPostProcessor;
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
		$this->registerOptimizers($container);
		$this->registerMetrics($container);
		$this->registerAvailability($container);
		$this->registerGuard($container);
		$this->registerProviders($container);
		$this->registerEmbeddings($container);
		$this->registerMcp($container);
		$this->neuronProcessors($container);
	}

	private function registerAvailability(Container $container): void
	{
		$container->singleton(CircuitBreakerStorageInterface::class, new FileCircuitBreakerStorage(failureThreshold: 5, openWindowSeconds: 60));
		$container->singleton(CacheStorageInterface::class, new FileCacheStorage());
		$container->singleton(ThrottleStorageInterface::class, new FileThrottleStorage());
		$container->bind(LoggingHttpClient::class, fn () => new LoggingHttpClient($container->resolve('logger.ai')));
	}

	private function registerOptimizers(Container $container): void
	{
		$container->singleton(PromptOptimizerInterface::class, new PromptOptimizer());
		$container->singleton(McpTokenCalculator::class, new McpTokenCalculator());
	}

	private function registerGuard(Container $container): void
	{
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

		$container->singleton(AuditStorageInterface::class, new JsonlAuditStorage());
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
		$container->singleton('logger.ai', new FileLogger($container->resolve(ClockInterface::class), Path::getStorageDir() . '/ai/logs'));
		$container->singleton(MetricStorageInterface::class, new JsonlMetricStorage());
		$container->singleton(QualityEvaluatorInterface::class, new NullQualityEvaluator());
	}

	private function registerMcp(Container $container): void
	{
		$mcpStorage = new McpTokenJsonlStorage();
		$container->singleton(McpTokenStorageInterface::class, $mcpStorage);
		$container->singleton(McpTokenObserver::class, new McpTokenObserver(calculator: $container->resolve(McpTokenCalculator::class), storage: $mcpStorage));
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
