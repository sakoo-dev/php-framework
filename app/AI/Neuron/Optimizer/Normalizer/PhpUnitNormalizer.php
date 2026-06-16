<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Normalizer;

use App\AI\Neuron\Optimizer\ToolResultNormalizerInterface;

/**
 * Normalizes PHPUnit output by aggregating repeated test outcomes and removing verbose traces.
 */
final readonly class PhpUnitNormalizer implements ToolResultNormalizerInterface
{
	public function normalize(string $rawOutput): string
	{
		$lines = explode("\n", $rawOutput);
		$filtered = [];
		$passCount = 0;
		$failCount = 0;

		foreach ($lines as $line) {
			$trimmed = trim($line);

			if ('' === $trimmed) {
				continue;
			}

			if ('PASS' === $trimmed || str_starts_with($trimmed, '.')) {
				++$passCount;

				continue;
			}

			if (str_starts_with($trimmed, 'FAIL') || str_starts_with($trimmed, 'F')) {
				++$failCount;

				continue;
			}

			if (str_starts_with($trimmed, 'PHPUnit') && str_contains($trimmed, 'by Sebastian Bergmann')) {
				continue;
			}

			$filtered[] = $line;
		}

		if ($passCount > 0) {
			array_unshift($filtered, "PASS (x{$passCount})");
		}

		if ($failCount > 0) {
			array_unshift($filtered, "FAIL (x{$failCount})");
		}

		return implode("\n", $filtered);
	}
}
