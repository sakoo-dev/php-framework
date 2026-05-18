<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Model;

use NeuronAI\Providers\AIProviderInterface;

/**
 * Unwraps a decorator chain to find the model name of the innermost provider.
 *
 * Resolution strategy (in priority order):
 *
 *   1. {@see ModelNameAwareInterface} — explicit contract; preferred for custom providers.
 *   2. Reflected `$model` property — covers all NeuronAI built-in providers
 *      (Anthropic, Gemini, Ollama, OpenAILike) which store the model name in a
 *      `protected string $model` property by convention but do not implement our interface.
 *   3. Class name of the outermost provider — safe fallback when neither applies.
 *
 * Decorators store their inner provider in a `$inner` property (project convention).
 * The resolver walks the chain layer by layer until it reaches a leaf provider.
 */
final class ModelNameResolver
{
	/**
	 * Returns the model name from the innermost provider in the decorator chain,
	 * or the fully-qualified class name of the outermost provider as a last resort.
	 */
	public static function resolve(AIProviderInterface $provider): string
	{
		$current = $provider;

		while (true) {
			if ($current instanceof ModelNameAwareInterface) {
				return $current->modelName();
			}

			$inner = self::unwrapInner($current);

			if (!$inner) {
				$fromProperty = self::readModelProperty($current);

				return $fromProperty ?? $provider::class;
			}

			$current = $inner;
		}
	}

	/**
	 * Reads the $inner property of a decorator to get the next layer.
	 * Returns null when no such property exists (leaf provider reached).
	 */
	private static function unwrapInner(AIProviderInterface $provider): ?AIProviderInterface
	{
		$reflection = new \ReflectionObject($provider);

		if (!$reflection->hasProperty('inner')) {
			return null;
		}

		$property = $reflection->getProperty('inner');
		$property->setAccessible(true);
		$value = $property->getValue($provider);

		return $value instanceof AIProviderInterface ? $value : null;
	}

	/**
	 * Reads the `$model` property via reflection from a leaf provider.
	 *
	 * All NeuronAI built-in providers (Anthropic, Gemini, Ollama, OpenAILike)
	 * store the model identifier in `protected string $model`. This covers them
	 * without requiring changes to vendor code.
	 *
	 * Returns null when the property does not exist or is not a non-empty string.
	 */
	private static function readModelProperty(AIProviderInterface $provider): ?string
	{
		$reflection = new \ReflectionObject($provider);

		while (true) {
			if ($reflection->hasProperty('model')) {
				$property = $reflection->getProperty('model');
				$property->setAccessible(true);
				$value = $property->getValue($provider);

				return (is_string($value) && '' !== $value) ? $value : null;
			}

			$parent = $reflection->getParentClass();

			if (!$parent) {
				break;
			}

			$reflection = $parent;
		}

		return null;
	}
}
