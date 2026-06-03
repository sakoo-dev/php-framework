<?php

declare(strict_types=1);

namespace App\AI\Agent;

use App\AI\Mcp\McpContextProvider;
use App\AI\Mcp\McpElements;
use App\AI\Neuron\Model\ModelNameResolver;
use App\AI\Neuron\Session\ChatHistory;
use App\AI\Neuron\Session\ChatSession;
use App\AI\Neuron\Session\SessionId;
use App\AI\Neuron\Tool\PromptFetchTool;
use App\AI\Neuron\Tool\ResourceFetchTool;
use App\AI\Neuron\Tool\RetrievalTool;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
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
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

abstract class Agent extends RAG
{
	private ?McpContextProvider $contextProvider = null;

	private static ?McpConnector $connector = null;

	private ?ChatSession $session = null;

	abstract public function getName(): string;

	abstract protected function agentInstructions(): string;

	/** @return string[] */
	abstract public function getExcludedTools(): array;

	/** @return string[] */
	abstract public function getExcludedContexts(): array;

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

	final protected function instructions(): string
	{
		$base = $this->agentInstructions();
		$extra = $this->contextProvider?->resolve() ?? [];

		return (string) new SystemPrompt(
			background: array_merge([$base], $extra),
		);
	}

	final protected function tools(): array
	{
		$all = $this->availableTools();
		$excluded = array_flip($this->getExcludedTools());

		return array_values(
			array_filter($all, fn (ToolInterface $tool) => !isset($excluded[$tool->getName()])),
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
			contextWindow: 50000,
		);
	}

	/** @return ToolInterface[] */
	protected function availableTools(): array
	{
		self::$connector ??= McpConnector::make(['command' => 'php', 'args' => ['assist', 'mcp:run']]);

		return [
			...FileSystemToolkit::make()->tools(),
			...CalculatorToolkit::make()->tools(),
			...CalendarToolkit::make()->tools(),
			...self::$connector->tools(),
			ResourceFetchTool::make(McpElements::class),
			PromptFetchTool::make(McpElements::class),
			new RetrievalTool($this),
		];
	}

	protected function ragNodes(): array
	{
		return [];
	}

	protected function startEvent(): AgentStartEvent
	{
		$tools = $this->bootstrapTools();
		$instructions = $this->resolveInstructions();

		return new AIInferenceEvent($instructions, $tools);
	}

	/** @return string[] */
	protected function availableContexts(): array
	{
		$contexts = [];

		$reflection = new \ReflectionClass(McpElements::class);
		$methods = $reflection->getMethods();

		/** @var array<\ReflectionAttribute<object>> */
		$attributes = [];

		foreach ($methods as $method) {
			$attributes = $attributes + $method->getAttributes(McpResource::class);
			$attributes = $attributes + $method->getAttributes(McpPrompt::class);
		}

		/** @var \ReflectionAttribute<object> $context */
		foreach ($attributes as $context) {
			$instance = $context->newInstance();

			if ($instance instanceof McpResource) {
				$contexts[] = $instance->uri;

				continue;
			}

			if ($instance instanceof McpPrompt) {
				if (null !== $instance->name) {
					$contexts[] = $instance->name;
				}
			}
		}

		return $contexts;
	}

	/** @return string[] */
	protected function neuronToolkitNames(): array
	{
		$toolkits = [
			...FileSystemToolkit::make()->tools(),
			...CalculatorToolkit::make()->tools(),
			...CalendarToolkit::make()->tools(),
		];

		return array_map(fn (ToolInterface $t): string => $t->getName(), $toolkits);
	}

	public function getModelName(): string
	{
		return ModelNameResolver::resolve($this->provider());
	}
}
