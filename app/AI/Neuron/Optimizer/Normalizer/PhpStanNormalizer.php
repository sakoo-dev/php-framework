<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Normalizer;

use App\AI\Neuron\Optimizer\ToolResultNormalizerInterface;

/**
 * Normalizes PHPStan output by deduplicating error patterns and focusing on unique issues.
 */
final readonly class PhpStanNormalizer implements ToolResultNormalizerInterface
{
	public function normalize(string $rawOutput): string
	{
		$lines = explode("\n", $rawOutput);
		$filtered = [];
		$errorPatterns = [];

		foreach ($lines as $line) {
			$trimmed = trim($line);

			if ('' === $trimmed) {
				continue;
			}

			if (str_starts_with($trimmed, 'Note:')) {
				continue;
			}

			if (preg_match('/^\s*\d+\/\d+/', $trimmed)) {
				continue;
			}

			if (preg_match('/^------ .+ ------$/', $trimmed)) {
				$filtered[] = $line;

				continue;
			}

			if (preg_match('/^\s*#\d+\s+(.+)$/', $trimmed, $matches)) {
				$pattern = preg_replace('/\b\d+\b/', 'N', $matches[1]);

				if (isset($errorPatterns[$pattern])) {
					++$errorPatterns[$pattern];

					continue;
				}

				$errorPatterns[$pattern] = 1;
			}

			$filtered[] = $line;
		}

		foreach ($errorPatterns as $pattern => $count) {
			if ($count > 1) {
				$filtered[] = "  (similar error repeated x{$count})";
			}
		}

		return implode("\n", $filtered);
	}
}
