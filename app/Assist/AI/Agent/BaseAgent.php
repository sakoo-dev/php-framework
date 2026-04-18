<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use App\Assist\AI\Mcp\McpContextProvider;
use App\Assist\AI\Mcp\McpElements;
use App\Assist\AI\Mcp\McpPromptFetchTool;
use App\Assist\AI\Mcp\McpResourceFetchTool;
use App\Assist\AI\Neuron\ChatHistory;
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

abstract class BaseAgent extends RAG
{
	private ?McpContextProvider $contextProvider = null;

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

	protected function chatHistory(): ChatHistoryInterface
	{
		$file = File::open(Disk::Local, Path::getStorageDir() . '/ai/chat-history/neuron_' . $this->getName() . '.chat');
		$file->create();

		return new ChatHistory(
			directory: Path::getStorageDir() . '/ai/chat-history',
			key: $this->getName(),
			contextWindow: 50000,
		);
	}

	/** @return ToolInterface[] */
	protected function availableTools(): array
	{
		return [
			...FileSystemToolkit::make()->tools(),
			...CalculatorToolkit::make()->tools(),
			...CalendarToolkit::make()->tools(),
			...McpConnector::make(['command' => 'php', 'args' => ['assist', 'mcp:run']])->tools(),
			McpResourceFetchTool::make(McpElements::class),
			McpPromptFetchTool::make(McpElements::class),
		];
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
}
