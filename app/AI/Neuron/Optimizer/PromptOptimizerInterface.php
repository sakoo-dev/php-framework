<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer;

/**
 * Optimizes prompt text by removing redundant content and reducing token usage.
 * Handles whitespace, stop words, repetition aggregation, and truncation.
 */
interface PromptOptimizerInterface
{
	/**
	 * Optimize prompt text to reduce token count while preserving meaning.
	 *
	 * @param string $text Raw prompt text
	 * @param null|int $maxLength Optional max length in characters (truncates if exceeded)
	 *
	 * @return string Optimized text
	 */
	public function optimize(string $text, ?int $maxLength = null): string;
}
