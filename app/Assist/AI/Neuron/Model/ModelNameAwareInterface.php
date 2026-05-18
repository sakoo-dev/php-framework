<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Model;

/**
 * Implemented by concrete AI providers (GapGpt, etc.) to expose their model name.
 *
 * Decorators (RetryProviderDecorator, CacheProviderDecorator, etc.) do NOT
 * implement this interface — callers use {@see ModelNameResolver::resolve()} to
 * unwrap the decorator chain and find the innermost implementing provider.
 */
interface ModelNameAwareInterface
{
	/**
	 * Returns the model identifier string as passed to the provider constructor
	 * (e.g. "gpt-4o", "claude-sonnet-4-5").
	 */
	public function modelName(): string;
}
