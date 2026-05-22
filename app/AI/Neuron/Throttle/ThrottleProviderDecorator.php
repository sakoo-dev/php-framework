<?php

declare(strict_types=1);

namespace App\AI\Neuron\Throttle;

use App\AI\Neuron\Throttle\Exception\ThrottleLimitExceededException;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\ToolMapperInterface;

/**
 * Provider decorator that rate-limits LLM calls per a composite key.
 *
 * The $compositeKey should encode both agent name and user identifier so that
 * per-user and per-agent limits are enforced independently, e.g. "agent:userId".
 * Limits are configured via ThrottleConfig.
 *
 * When the sliding-window limit is exceeded, ThrottleLimitExceededException is
 * thrown immediately with the number of seconds until a slot re-opens.
 */
final class ThrottleProviderDecorator implements AIProviderInterface
{
	public function __construct(
		private readonly AIProviderInterface $inner,
		private readonly ThrottleStorageInterface $storage,
		private readonly string $compositeKey,
		private readonly ThrottleConfig $config,
	) {}

	public function chat(Message ...$messages): Message
	{
		$this->guardThrottle();

		return $this->inner->chat(...$messages);
	}

	/**
	 * @return \Generator<mixed, mixed, mixed, Message>
	 */
	public function stream(Message ...$messages): \Generator
	{
		$this->guardThrottle();

		return yield from $this->inner->stream(...$messages);
	}

	public function structured(array|Message $messages, string $class, array $response_schema): Message
	{
		$this->guardThrottle();

		return $this->inner->structured($messages, $class, $response_schema);
	}

	public function systemPrompt(?string $prompt): AIProviderInterface
	{
		$this->inner->systemPrompt($prompt);

		return $this;
	}

	public function setTools(array $tools): AIProviderInterface
	{
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

	private function guardThrottle(): void
	{
		$allowed = $this->storage->consume($this->compositeKey, $this->config->maxRequests, $this->config->windowSeconds);

		if (!$allowed) {
			$retryAfter = $this->storage->retryAfter($this->compositeKey, $this->config->windowSeconds);

			throw new ThrottleLimitExceededException($retryAfter);
		}
	}
}
