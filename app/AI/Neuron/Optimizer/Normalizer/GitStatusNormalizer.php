<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Normalizer;

use App\AI\Neuron\Optimizer\ToolResultNormalizerInterface;

/**
 * Normalizes git-status output by removing redundant headers and branch info.
 */
final readonly class GitStatusNormalizer implements ToolResultNormalizerInterface
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

			if (str_starts_with($trimmed, 'On branch')) {
				continue;
			}

			if (str_contains($trimmed, 'Your branch is up to date with')) {
				continue;
			}

			if (str_contains($trimmed, 'nothing to commit, working tree clean')) {
				$filtered[] = 'Working tree clean';

				continue;
			}

			$filtered[] = $line;
		}

		return implode("\n", $filtered);
	}
}
