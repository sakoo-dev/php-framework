<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Maps tool names to their corresponding normalizer instances.
 * Matching is case-insensitive and falls back to prefix matching
 * so that dynamic MCP tool names (e.g. "git_status_v2") are resolved
 * against the closest registered prefix.
 */
final class ToolResultNormalizerRegistry
{
	/**
	 * @var array<string, ToolResultNormalizerInterface>
	 */
	private array $normalizers = [];

	/**
	 * @var array<string, string> Resolved cache of raw tool name → matched key
	 */
	private array $resolvedCache = [];

	/**
	 * @param array<string, ToolResultNormalizerInterface> $normalizers Map of tool names to normalizer instances
	 */
	public function __construct(array $normalizers)
	{
		foreach ($normalizers as $name => $normalizer) {
			$this->normalizers[strtolower($name)] = $normalizer;
		}
	}

	public function has(string $toolName): bool
	{
		return null !== $this->resolve($toolName);
	}

	/**
	 * @throws Exception
	 */
	public function get(string $toolName): ToolResultNormalizerInterface
	{
		$resolved = $this->resolve($toolName);

		if (null === $resolved) {
			throw new Exception("No normalizer registered for tool: {$toolName}");
		}

		return $this->normalizers[$resolved];
	}

	public function normalize(string $toolName, string $rawOutput): string
	{
		$resolved = $this->resolve($toolName);

		if (null === $resolved) {
			return $rawOutput;
		}

		return $this->normalizers[$resolved]->normalize($rawOutput);
	}

	private function resolve(string $toolName): ?string
	{
		$lower = strtolower($toolName);

		if (isset($this->resolvedCache[$lower])) {
			return $this->resolvedCache[$lower];
		}

		if (isset($this->normalizers[$lower])) {
			$this->resolvedCache[$lower] = $lower;

			return $lower;
		}

		$matched = $this->matchByPrefix($lower);

		if (null !== $matched) {
			$this->resolvedCache[$lower] = $matched;
		}

		return $matched;
	}

	private function matchByPrefix(string $lower): ?string
	{
		$bestKey = null;
		$bestLength = 0;

		foreach (array_keys($this->normalizers) as $key) {
			if (!str_starts_with($lower, $key)) {
				continue;
			}

			if (strlen($key) > $bestLength) {
				$bestKey = $key;
				$bestLength = strlen($key);
			}
		}

		return $bestKey;
	}
}
