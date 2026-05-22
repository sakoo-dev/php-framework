<?php

declare(strict_types=1);

namespace App\AI\Neuron;

use App\AI\Neuron\Cache\CacheProviderDecorator;
use App\AI\Neuron\Cache\CacheStorageInterface;
use App\AI\Neuron\CircuitBreaker\CircuitBreakerProviderDecorator;
use App\AI\Neuron\CircuitBreaker\CircuitBreakerStorageInterface;
use App\AI\Neuron\Fallback\FallbackProviderDecorator;
use App\AI\Neuron\Retry\RetryPolicy;
use App\AI\Neuron\Retry\RetryProviderDecorator;
use App\AI\Neuron\Throttle\ThrottleConfig;
use App\AI\Neuron\Throttle\ThrottleProviderDecorator;
use App\AI\Neuron\Throttle\ThrottleStorageInterface;
use NeuronAI\Providers\AIProviderInterface;

/**
 * Fluent builder that composes availability decorators around a primary provider.
 *
 * Decoration order (outer → inner):
 *   Cache → Throttle → CircuitBreaker → Retry → (Fallback | primary)
 *
 * - Cache hits are returned without touching any other layer.
 * - Throttle rejects over-quota requests before any live call.
 * - CircuitBreaker gates remaining calls before retries are attempted.
 * - Retry handles transient failures from any single provider.
 * - Fallback is the innermost live layer, tried after retries are exhausted.
 *
 * All layers are optional — call only the methods you need.
 *
 * Note: Throttle requires a runtime composite key (agentName:userId). Wire it
 * in the command or workflow layer where the key is known, not at boot time.
 */
final class HighAvailableProviderBuilder
{
	private ?RetryPolicy $retryPolicy = null;
	private ?CircuitBreakerStorageInterface $circuitBreakerStorage = null;
	private ?string $circuitBreakerKey = null;
	private ?CacheStorageInterface $cacheStorage = null;
	private ?string $cacheModelName = null;
	private int $cacheTtlSeconds = 3600;
	private ?ThrottleStorageInterface $throttleStorage = null;
	private ?string $throttleKey = null;
	private ?ThrottleConfig $throttleConfig = null;

	/** @var AIProviderInterface[] */
	private array $fallbacks = [];

	private function __construct(
		private readonly AIProviderInterface $primary,
	) {}

	public static function wrap(AIProviderInterface $primary): self
	{
		return new self($primary);
	}

	public function withRetry(RetryPolicy $policy): self
	{
		$this->retryPolicy = $policy;

		return $this;
	}

	public function withCircuitBreaker(CircuitBreakerStorageInterface $storage, string $key): self
	{
		$this->circuitBreakerStorage = $storage;
		$this->circuitBreakerKey = $key;

		return $this;
	}

	public function withThrottle(
		ThrottleStorageInterface $storage,
		string $compositeKey,
		ThrottleConfig $config,
	): self {
		$this->throttleStorage = $storage;
		$this->throttleKey = $compositeKey;
		$this->throttleConfig = $config;

		return $this;
	}

	public function withCache(CacheStorageInterface $storage, string $modelName, int $ttlSeconds = 3600): self
	{
		$this->cacheStorage = $storage;
		$this->cacheModelName = $modelName;
		$this->cacheTtlSeconds = $ttlSeconds;

		return $this;
	}

	/** @param AIProviderInterface[] $providers ordered fallback chain */
	public function withFallbacks(array $providers): self
	{
		$this->fallbacks = $providers;

		return $this;
	}

	public function build(): AIProviderInterface
	{
		$provider = !empty($this->fallbacks) ? new FallbackProviderDecorator([$this->primary, ...$this->fallbacks]) : $this->primary;

		if ($this->retryPolicy) {
			$provider = new RetryProviderDecorator($provider, $this->retryPolicy);
		}

		if ($this->circuitBreakerStorage && $this->circuitBreakerKey) {
			$provider = new CircuitBreakerProviderDecorator($provider, $this->circuitBreakerStorage, $this->circuitBreakerKey);
		}

		if ($this->throttleStorage && $this->throttleKey && $this->throttleConfig) {
			$provider = new ThrottleProviderDecorator($provider, $this->throttleStorage, $this->throttleKey, $this->throttleConfig);
		}

		if ($this->cacheStorage && $this->cacheModelName) {
			$provider = new CacheProviderDecorator($provider, $this->cacheStorage, $this->cacheModelName, $this->cacheTtlSeconds);
		}

		return $provider;
	}
}
