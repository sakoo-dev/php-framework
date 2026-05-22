<?php

declare(strict_types=1);

namespace App\AI\Neuron\Retry;

use App\AI\Neuron\Retry\Exception\MaxRetriesExceededException;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\MessageMapperInterface;
use NeuronAI\Providers\ToolMapperInterface;

/**
 * Provider decorator that retries the inner provider call on transient failure
 * using exponential backoff defined by RetryPolicy.
 *
 * Non-retryable exceptions (tagged with NonRetryableExceptionInterface) are
 * re-thrown immediately without consuming retry attempts — e.g. CircuitOpenException,
 * ThrottleLimitExceededException, AllProvidersFailedException.
 *
 * After all attempts are exhausted, throws MaxRetriesExceededException
 * with the last caught exception as its previous.
 *
 * Note: stream() retries are limited to generator construction, not streaming
 * execution, due to generator semantics — keep this in mind for long streams.
 */
final class RetryProviderDecorator implements AIProviderInterface
{
	public function __construct(
		private readonly AIProviderInterface $inner,
		private readonly RetryPolicy $policy,
	) {}

	public function chat(Message ...$messages): Message
	{
		return $this->attempt(fn (): Message => $this->inner->chat(...$messages));
	}

	/**
	 * @return \Generator<mixed, mixed, mixed, Message>
	 */
	public function stream(Message ...$messages): \Generator
	{
		$lastException = null;

		for ($attempt = 1; $attempt <= $this->policy->maxAttempts; ++$attempt) {
			try {
				return yield from $this->inner->stream(...$messages);
			} catch (\Throwable $e) {
				if (!$this->policy->isRetryable($e)) {
					throw $e;
				}

				$lastException = $e;

				if ($attempt < $this->policy->maxAttempts) {
					usleep($this->policy->delayMsForAttempt($attempt) * 1000);
				}
			}
		}

		throw new MaxRetriesExceededException(
			"LLM call failed after {$this->policy->maxAttempts} attempts: " . $lastException?->getMessage(),
			previous: $lastException,
		);
	}

	public function structured(array|Message $messages, string $class, array $response_schema): Message
	{
		return $this->attempt(fn (): Message => $this->inner->structured($messages, $class, $response_schema));
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
	 * Runs $fn with retry-on-failure according to RetryPolicy.
	 * Non-retryable exceptions are re-thrown immediately.
	 *
	 * @template T
	 *
	 * @param callable(): T $fn
	 *
	 * @return T
	 */
	private function attempt(callable $fn): mixed
	{
		$lastException = null;

		for ($attempt = 1; $attempt <= $this->policy->maxAttempts; ++$attempt) {
			try {
				return $fn();
			} catch (\Throwable $e) {
				if (!$this->policy->isRetryable($e)) {
					throw $e;
				}

				$lastException = $e;

				if ($attempt < $this->policy->maxAttempts) {
					usleep($this->policy->delayMsForAttempt($attempt) * 1000);
				}
			}
		}

		throw new MaxRetriesExceededException(
			"LLM call failed after {$this->policy->maxAttempts} attempts: " . $lastException?->getMessage(),
			previous: $lastException,
		);
	}
}
