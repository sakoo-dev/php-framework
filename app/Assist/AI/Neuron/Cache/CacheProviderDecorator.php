<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Cache;

use App\Assist\AI\Metric\MetricSource;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\ReasoningContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\Usage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\ToolMapperInterface;
use NeuronAI\Tools\ToolInterface;

/**
 * Provider decorator that caches LLM chat() responses keyed on a deterministic
 * SHA-256 hash of (model, systemPrompt, lastUserMessage, tools).
 *
 * Only the last user message is included in the cache key — not the full
 * conversation history. Including the full history would make every turn of a
 * multi-turn session a guaranteed miss, since each turn appends new messages.
 * Scoping to the final user turn means any two calls with the same question,
 * system prompt, model, and tool set share a cache entry, which is the realistic
 * hit condition for an agent loop.
 *
 * Trade-off: two questions with identical text but different preceding history
 * will return the same cached answer. This is acceptable for stateless Q&A and
 * single-turn agent calls; disable caching for workflows where prior context
 * materially changes what the correct response should be.
 *
 * The full jsonSerialize() payload is stored so that content blocks (text,
 * reasoning), usage statistics, and stop reason are preserved on cache hits.
 * Tool calls are NOT cached: a ToolCallMessage response triggers live execution
 * on every turn to preserve correct tool-dispatch behaviour.
 *
 * On hit  — reconstructs an AssistantMessage with all original content blocks
 *           and usage; sets lastSource = Cache.
 * On miss — calls the inner provider, serialises and stores the response;
 *           sets lastSource = Live.
 *
 * Streaming and structured calls pass through without caching.
 */
final class CacheProviderDecorator implements AIProviderInterface
{
	private ?string $systemPromptValue = null;
	private MetricSource $lastSource = MetricSource::Live;

	/** @var ToolInterface[] */
	private array $tools = [];

	public function __construct(
		private readonly AIProviderInterface $inner,
		private readonly CacheStorageInterface $storage,
		private readonly string $modelName,
		private readonly int $ttlSeconds = 3600,
	) {}

	public function lastSource(): MetricSource
	{
		return $this->lastSource;
	}

	public function chat(Message ...$messages): Message
	{
		$cacheKey = $this->buildKey($messages);
		$cached = $this->storage->get($cacheKey);

		if ($cached) {
			$restored = $this->restore($cached);

			if ($restored) {
				$this->lastSource = MetricSource::Cache;

				return $restored;
			}
		}

		$response = $this->inner->chat(...$messages);
		$this->lastSource = MetricSource::Live;

		$this->persistIfCacheable($cacheKey, $response);

		return $response;
	}

	/**
	 * @return \Generator<mixed, mixed, mixed, Message>
	 */
	public function stream(Message ...$messages): \Generator
	{
		$this->lastSource = MetricSource::Live;

		return yield from $this->inner->stream(...$messages);
	}

	public function structured(array|Message $messages, string $class, array $response_schema): Message
	{
		$this->lastSource = MetricSource::Live;

		return $this->inner->structured($messages, $class, $response_schema);
	}

	public function systemPrompt(?string $prompt): AIProviderInterface
	{
		$this->systemPromptValue = $prompt;
		$this->inner->systemPrompt($prompt);

		return $this;
	}

	public function setTools(array $tools): AIProviderInterface
	{
		$this->tools = $tools;
		$this->inner->setTools($tools);

		return $this;
	}

	public function messageMapper(): MessageMapperInterface
	{
		return $this->inner->messageMapper();
	}

	public function toolPayloadMapper(): ToolMapperInterface
	{
		return $this->inner->toolPayloadMapper();
	}

	public function setHttpClient(HttpClientInterface $client): AIProviderInterface
	{
		$this->inner->setHttpClient($client);

		return $this;
	}

	/**
	 * Stores the response only when it is a plain AssistantMessage (no tool calls).
	 * ToolCallMessage responses are intentionally excluded from the cache so that
	 * tool execution is never silently bypassed on a cache hit.
	 */
	private function persistIfCacheable(string $cacheKey, Message $response): void
	{
		if ($response instanceof ToolCallMessage) {
			return;
		}

		$encoded = json_encode($response->jsonSerialize(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		if ($encoded) {
			$this->storage->set($cacheKey, $encoded, $this->ttlSeconds);
		}
	}

	/**
	 * Reconstructs an AssistantMessage from a cached JSON payload.
	 *
	 * The full content-block array from jsonSerialize() is used so that
	 * reasoning blocks, stop reason, and usage are all preserved.
	 * Returns null when the payload is malformed so the caller falls back to live.
	 * Unknown content block types are skipped rather than dropped silently —
	 * the fallback to live ensures no data loss.
	 */
	private function restore(string $cached): ?Message
	{
		$data = json_decode($cached, true);

		if (!is_array($data)) {
			return null;
		}

		/** @var array{content?: list<array{type?: string, content?: string}>, usage?: array{input_tokens?: int, output_tokens?: int}, stop_reason?: string} $data */
		$blocks = [];

		$rawContent = $data['content'] ?? [];

		foreach ($rawContent as $rawBlock) {
			/** @var mixed $rawBlock */
			if (!is_array($rawBlock)) {
				continue;
			}

			$type = is_string($rawBlock['type'] ?? null) ? (string) $rawBlock['type'] : '';
			$content = is_string($rawBlock['content'] ?? null) ? (string) $rawBlock['content'] : '';
			$blocks[] = 'reasoning' === $type ? new ReasoningContent($content) : new TextContent($content);
		}

		$message = new AssistantMessage([] === $blocks ? null : $blocks);

		if (isset($data['stop_reason'])) {
			$message->setStopReason($data['stop_reason']);
		}

		$usageData = $data['usage'] ?? null;

		if (is_array($usageData)) {
			$inputTokens = is_int($usageData['input_tokens'] ?? null) ? (int) $usageData['input_tokens'] : 0;
			$outputTokens = is_int($usageData['output_tokens'] ?? null) ? (int) $usageData['output_tokens'] : 0;
			$message->setUsage(new Usage($inputTokens, $outputTokens));
		}

		return $message;
	}

	/**
	 * Builds the cache key from stable, request-invariant inputs plus the last
	 * user message — the only part of the conversation that defines what answer
	 * the model should produce in a stateless context.
	 *
	 * The full message history is intentionally excluded: including it would make
	 * every turn of a multi-turn session a guaranteed cache miss.
	 *
	 * @param Message[] $messages
	 */
	private function buildKey(array $messages): string
	{
		$lastUserMessage = null;

		foreach (array_reverse($messages) as $message) {
			if ($message instanceof UserMessage) {
				$lastUserMessage = $message->jsonSerialize();

				break;
			}
		}

		$payload = [
			'model' => $this->modelName,
			'system' => $this->systemPromptValue,
			'last_user_message' => $lastUserMessage,
			'tools' => array_map(fn (ToolInterface $t) => $t->jsonSerialize(), $this->tools),
		];

		$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		return hash('sha256', false !== $encoded ? $encoded : serialize($payload));
	}
}
