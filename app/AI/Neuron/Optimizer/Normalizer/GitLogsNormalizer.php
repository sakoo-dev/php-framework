<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Normalizer;

use App\AI\Neuron\Optimizer\ToolResultNormalizerInterface;

/**
 * Normalizes git-log output by compressing commit metadata and focusing on essential info.
 */
final readonly class GitLogsNormalizer implements ToolResultNormalizerInterface
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

			if (str_starts_with($trimmed, 'Author:') && str_contains($trimmed, '<')) {
				$filtered[] = preg_replace('/Author:\s*(.+?)\s*<.+?>/', 'Author: $1', $trimmed);

				continue;
			}

			if (str_starts_with($trimmed, 'Date:')) {
				continue;
			}

			$filtered[] = $line;
		}

		return implode("\n", $filtered);
	}
}
