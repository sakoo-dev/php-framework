<?php

declare(strict_types=1);

namespace App\AI\Neuron\CircuitBreaker;

use App\AI\Neuron\AIProviderDecorator;
use App\AI\Neuron\CircuitBreaker\Exception\CircuitOpenException;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\ToolMapperInterface;

/**
 * Provider decorator implementing the Circuit Breaker pattern.
 *
 * Closed   — passes every call through; records failures.
 * Open     — rejects immediately with CircuitOpenException.
 * HalfOpen — allows exactly one probe call via claimProbe(); all other
 *            concurrent callers are rejected until the probe resolves.
 *            Success closes the circuit; Failure re-opens it.
 *
 * The providerKey namespaces state in storage so multiple providers coexist.
 */
final class CircuitBreakerProviderDecorator implements AIProviderDecorator
{
	public function __construct(
		private readonly AIProviderInterface $inner,
		private readonly CircuitBreakerStorageInterface $storage,
		private readonly string $providerKey,
	) {}

	public function chat(Message ...$messages): Message
	{
		return $this->withCircuit(fn (): Message => $this->inner->chat(...$messages));
	}

	/**
	 * @return \Generator<mixed, mixed, mixed, Message>
	 */
	public function stream(Message ...$messages): \Generator
	{
		$this->guardCircuit();

		try {
			$result = yield from $this->inner->stream(...$messages);
			$this->storage->recordSuccess($this->providerKey);

			return $result;
		} catch (\Throwable $e) {
			$this->storage->recordFailure($this->providerKey);

			throw $e;
		}
	}

	public function structured(array|Message $messages, string $class, array $response_schema): Message
	{
		return $this->withCircuit(fn (): Message => $this->inner->structured($messages, $class, $response_schema));
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

	/**
	 * Runs $fn under circuit-breaker protection.
	 * Gates the call, records the outcome, and re-throws on failure.
	 *
	 * @template T
	 *
	 * @param callable(): T $fn
	 *
	 * @return T
	 */
	private function withCircuit(callable $fn): mixed
	{
		$this->guardCircuit();

		try {
			$result = $fn();
			$this->storage->recordSuccess($this->providerKey);

			return $result;
		} catch (\Throwable $e) {
			$this->storage->recordFailure($this->providerKey);

			throw $e;
		}
	}

	/**
	 * Throws CircuitOpenException when the circuit must block the call.
	 *
	 * Open     — always rejects.
	 * HalfOpen — rejects unless this call wins the single probe slot.
	 *            Prevents concurrent calls from all probing a degraded provider.
	 */
	private function guardCircuit(): void
	{
		$state = $this->storage->getState($this->providerKey);

		if (CircuitState::Open === $state) {
			throw new CircuitOpenException(
				"Circuit for '{$this->providerKey}' is open — request rejected to prevent cascade failure.",
			);
		}

		if (CircuitState::HalfOpen === $state && !$this->storage->claimProbe($this->providerKey)) {
			throw new CircuitOpenException(
				"Circuit for '{$this->providerKey}' is half-open — probe already in flight, request rejected.",
			);
		}
	}
}
