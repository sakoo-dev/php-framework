<?php

declare(strict_types=1);

namespace App\AI\Agent;

use App\AI\Mcp\LineDelimitedMcpConnector;
use App\AI\Mcp\McpContextProvider;
use App\AI\Mcp\McpElements;
use App\AI\Mcp\McpTokenCalculator;
use App\AI\Neuron\AIProviderDecorator;
use App\AI\Neuron\Middleware\ToolResultOptimizationMiddleware;
use App\AI\Neuron\Middleware\ToolResultPruningMiddleware;
use App\AI\Neuron\Optimizer\Normalizer\ComposerNormalizer;
use App\AI\Neuron\Optimizer\Normalizer\GitDiffNormalizer;
use App\AI\Neuron\Optimizer\Normalizer\GitLogsNormalizer;
use App\AI\Neuron\Optimizer\Normalizer\GitStatusNormalizer;
use App\AI\Neuron\Optimizer\Normalizer\PhpStanNormalizer;
use App\AI\Neuron\Optimizer\Normalizer\PhpUnitNormalizer;
use App\AI\Neuron\Optimizer\PromptOptimizerInterface;
use App\AI\Neuron\Optimizer\Strategy\RecentToolResultsPruningStrategy;
use App\AI\Neuron\Optimizer\ToolResultNormalizerRegistry;
use App\AI\Neuron\Session\ChatHistory;
use App\AI\Neuron\Session\ChatSession;
use App\AI\Neuron\Session\SessionId;
use App\AI\Neuron\Tool\SearchMcpToolsTool;
use NeuronAI\Agent\Events\AgentStartEvent;
use NeuronAI\Agent\Events\AIInferenceEvent;
use NeuronAI\Agent\SystemPrompt;
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
use NeuronAI\Workflow\Middleware\WorkflowMiddleware;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

abstract class Agent extends RAG
{
	public const int CONTEXT_WINDOW = 50000;
	private ?McpContextProvider $contextProvider = null;
	private ?ChatSession $session = null;
	private static McpConnector $connector;

	abstract public function getName(): string;

	abstract protected function agentInstructions(): string;

	/** @return string[] */
	protected function contexts(): array
	{
		return [];
	}

	/**
	 * @return ToolInterface[]
	 */
	abstract protected function includedTools(): array;

	/**
	 * Declares whether this agent benefits from Claude's extended-thinking feature.
	 * Agents that perform deep reasoning (architecture, design, hard debugging) should
	 * override this to return true. General-purpose agents stay at the default false
	 * to avoid the per-request thinking overhead for trivial turns.
	 */
	protected function supportsThinking(): bool
	{
		return false;
	}

	/**
	 * Binds a ChatSession to this agent so that chatHistory() uses the correct
	 * session-scoped file. Call this before the first chat() invocation.
	 */
	final public function withSession(ChatSession $session): static
	{
		$this->session = $session;

		return $this;
	}

	final public function withMcpContext(McpContextProvider $provider): static
	{
		$this->contextProvider = $provider;

		return $this;
	}

	public function shouldApplyGuardrails(): bool
	{
		return false;
	}

	final protected function instructions(): string
	{
		$base = $this->agentInstructions();
		$contexts = $this->contextProvider?->resolve($this->contexts()) ?? [];

		return (string) new SystemPrompt(
			background: array_merge([$base], $contexts),
		);
	}

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

	/**
	 * Returns a ChatHistory scoped to the active session. If no session was bound
	 * via withSession(), a one-off anonymous session is generated — this path exists
	 * only for programmatic / test usage where session management is not required.
	 */
	protected function chatHistory(): ChatHistoryInterface
	{
		$resolved = $this->session ?? new ChatSession(SessionId::generate(), $this->getName());

		$historyFile = File::open(Disk::Local, $resolved->filePath(Path::getStorageDir()));
		$historyFile->create();

		return new ChatHistory(
			directory: Path::getStorageDir() . '/ai/chat-history',
			key: $resolved->historyKey(),
			contextWindow: self::CONTEXT_WINDOW,
		);
	}

	/**
	 * @return class-string
	 */
	protected function mcpElementsClass(): string
	{
		return McpElements::class;
	}

	/** @return ToolInterface[] */
	protected function fileSystemTools(): array
	{
		return FileSystemToolkit::make()->tools();
	}

	/** @return ToolInterface[] */
	protected function calculatorTools(): array
	{
		return CalculatorToolkit::make()->tools();
	}

	/** @return ToolInterface[] */
	protected function calendarTools(): array
	{
		return CalendarToolkit::make()->tools();
	}

	protected function mcpTools(): McpConnector
	{
		self::$connector ??= LineDelimitedMcpConnector::make(['command' => 'php', 'args' => [Path::getRootDir() . '/assist', 'mcp:run']]);

		return self::$connector;
	}

	protected function ragNodes(): array
	{
		return [];
	}

	protected function tools(): array
	{
		$includedTools = $this->includedTools();
		$availableTools = array_map(fn (ToolInterface $tool) => ['name' => $tool->getName(), 'description' => $tool->getDescription() ?? ''], $includedTools);

		return [
			new SearchMcpToolsTool($availableTools),
			...$includedTools,
		];
	}

	protected function startEvent(): AgentStartEvent
	{
		$tools = $this->bootstrapTools();
		$instructions = $this->resolveInstructions();

		return new AIInferenceEvent($instructions, $tools);
	}

	/** @return WorkflowMiddleware[] */
	protected function globalMiddleware(): array
	{
		$registry = new ToolResultNormalizerRegistry([
			'git-status' => new GitStatusNormalizer(),
			'git_status' => new GitStatusNormalizer(),
			'git-diff' => new GitDiffNormalizer(),
			'git_diff' => new GitDiffNormalizer(),
			'git-log' => new GitLogsNormalizer(),
			'git_log' => new GitLogsNormalizer(),
			'git-logs' => new GitLogsNormalizer(),
			'composer' => new ComposerNormalizer(),
			'composer-install' => new ComposerNormalizer(),
			'composer-update' => new ComposerNormalizer(),
			'phpunit' => new PhpUnitNormalizer(),
			'php-unit' => new PhpUnitNormalizer(),
			'test' => new PhpUnitNormalizer(),
			'test_run' => new PhpUnitNormalizer(),
			'run_tests_filtered' => new PhpUnitNormalizer(),
			'phpstan' => new PhpStanNormalizer(),
			'php-stan' => new PhpStanNormalizer(),
			'phpstan_analyse' => new PhpStanNormalizer(),
			'static-analysis' => new PhpStanNormalizer(),
		]);

		return [
			new ToolResultOptimizationMiddleware($registry, resolve(PromptOptimizerInterface::class), resolve(McpTokenCalculator::class)),
			new ToolResultPruningMiddleware(new RecentToolResultsPruningStrategy(15)),
		];
	}

	public function getModelName(): string
	{
		return $this->resolveModelName($this->provider());
	}

	public function getProviderName(): string
	{
		return $this->resolveProviderName($this->provider());
	}

	private function resolveModelName(AIProviderInterface $provider): string
	{
		if ($provider instanceof AIProviderDecorator) {
			/** @var AIProviderInterface $inner */
			$inner = $this->readProperty($provider, 'inner');

			return $this->resolveModelName($inner);
		}

		/** @var string $modelName */
		$modelName = $this->readProperty($provider, 'model');

		return (string) $modelName;
	}

	private function resolveProviderName(AIProviderInterface $provider): string
	{
		if ($provider instanceof AIProviderDecorator) {
			/** @var AIProviderInterface $inner */
			$inner = $this->readProperty($provider, 'inner');

			return $this->resolveProviderName($inner);
		}

		/** @var string $providerName */
		$providerName = $this->readProperty($provider, 'baseUri') ?? $this->readProperty($provider, 'url') ?? '';

		return (string) $providerName;
	}

	private function readProperty(AIProviderInterface $provider, string $property): mixed
	{
		$reflection = new \ReflectionObject($provider);

		if (!$reflection->hasProperty($property)) {
			return null;
		}

		$property = $reflection->getProperty($property);
		$property->setAccessible(true);
		$value = $property->getValue($provider);

		return $value ?: null;
	}
}
