<?php

declare(strict_types=1);

namespace App\AI\Neuron\Fallback;

use App\AI\Neuron\Fallback\Exception\AllProvidersFailedException;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\ToolMapperInterface;

/**
 * Provider decorator that tries each provider in order, falling back to the
 * next one when the current throws. All providers receive the same system
 * prompt and tools so they are interchangeable from the agent's perspective.
 *
 * Throws AllProvidersFailedException when every provider in the chain fails,
 * wrapping the last caught exception as previous.
 */
final class FallbackProviderDecorator implements AIProviderInterface
{
	/**
	 * @param AIProviderInterface[] $providers ordered list; first is primary
	 */
	public function __construct(private readonly array $providers)
	{
		if (!$providers) {
			throw new \InvalidArgumentException('FallbackProviderDecorator requires at least one provider.');
		}
	}

	public function chat(Message ...$messages): Message
	{
		return $this->tryEach(fn (AIProviderInterface $p): Message => $p->chat(...$messages));
	}

	/**
	 * @return \Generator<mixed, mixed, mixed, Message>
	 */
	public function stream(Message ...$messages): \Generator
	{
		$lastException = null;

		foreach ($this->providers as $provider) {
			try {
				return yield from $provider->stream(...$messages);
			} catch (\Throwable $e) {
				$lastException = $e;
			}
		}

		throw new AllProvidersFailedException(
			'All providers failed: ' . $lastException?->getMessage(),
			previous: $lastException,
		);
	}

	public function structured(array|Message $messages, string $class, array $response_schema): Message
	{
		return $this->tryEach(fn (AIProviderInterface $p): Message => $p->structured($messages, $class, $response_schema));
	}

	public function systemPrompt(?string $prompt): AIProviderInterface
	{
		foreach ($this->providers as $provider) {
			$provider->systemPrompt($prompt);
		}

		return $this;
	}

	public function setTools(array $tools): AIProviderInterface
	{
		foreach ($this->providers as $provider) {
			$provider->setTools($tools);
		}

		return $this;
	}

	public function messageMapper(): MessageMapperInterface
	{
		return $this->providers[0]->messageMapper();
	}

	public function toolPayloadMapper(): ToolMapperInterface
	{
		return $this->providers[0]->toolPayloadMapper();
	}

	public function setHttpClient(HttpClientInterface $client): AIProviderInterface
	{
		foreach ($this->providers as $provider) {
			$provider->setHttpClient($client);
		}

		return $this;
	}

	/**
	 * Iterates the provider chain until $call succeeds or all providers are exhausted.
	 *
	 * @template T
	 *
	 * @param callable(AIProviderInterface): T $call
	 *
	 * @return T
	 */
	private function tryEach(callable $call): mixed
	{
		$lastException = null;

		foreach ($this->providers as $provider) {
			try {
				return $call($provider);
			} catch (\Throwable $e) {
				$lastException = $e;
			}
		}

		throw new AllProvidersFailedException(
			'All providers failed: ' . $lastException?->getMessage(),
			previous: $lastException,
		);
	}
}
