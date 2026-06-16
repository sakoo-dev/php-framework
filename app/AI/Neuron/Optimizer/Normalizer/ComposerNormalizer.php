<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Normalizer;

use App\AI\Neuron\Optimizer\ToolResultNormalizerInterface;

/**
 * Normalizes Composer output by removing verbose dependency trees and lock file noise.
 */
final readonly class ComposerNormalizer implements ToolResultNormalizerInterface
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

			if (str_starts_with($trimmed, 'Loading composer repositories')) {
				continue;
			}

			if (str_contains($trimmed, 'Generating autoload files')) {
				$filtered[] = 'Autoload generated';

				continue;
			}

			if (preg_match('/^\s*-\s+(Installing|Updating|Removing)\s+(.+)/', $trimmed, $matches)) {
				$filtered[] = "{$matches[1]}: {$matches[2]}";

				continue;
			}

			if (str_contains($trimmed, 'packages you are using are looking for funding')) {
				continue;
			}

			$filtered[] = $line;
		}

		return implode("\n", $filtered);
	}
}
