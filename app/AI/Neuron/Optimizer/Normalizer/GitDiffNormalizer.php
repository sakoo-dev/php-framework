<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Normalizer;

use App\AI\Neuron\Optimizer\ToolResultNormalizerInterface;

/**
 * Normalizes git-diff output by removing file mode headers and reducing context noise.
 */
final readonly class GitDiffNormalizer implements ToolResultNormalizerInterface
{
	public function normalize(string $rawOutput): string
	{
		$lines = explode("\n", $rawOutput);
		$filtered = [];

		foreach ($lines as $line) {
			$trimmed = trim($line);

			if ('' === $trimmed) {
				continue;
			}

			if (str_starts_with($trimmed, 'index ')) {
				continue;
			}

			if (preg_match('/^(old|new) mode \d+$/', $trimmed)) {
				continue;
			}

			$filtered[] = $line;
		}

		return implode("\n", $filtered);
	}
}
