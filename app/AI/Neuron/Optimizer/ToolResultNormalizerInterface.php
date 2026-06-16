<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer;

/**
 * Normalizes tool results to reduce token usage before they enter the next LLM call.
 * Each normalizer handles specific tool output patterns (git, composer, phpunit, etc.).
 */
interface ToolResultNormalizerInterface
{
	/**
	 * Normalize raw tool output by removing boilerplate, aggregating repetitions,
	 * and compressing verbose metadata.
	 *
	 * @param string $rawOutput Raw tool result string
	 *
	 * @return string Optimized output
	 */
	public function normalize(string $rawOutput): string;
}
