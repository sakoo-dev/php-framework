<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

/**
 * Port for every content detection strategy.
 *
 * Implementations inspect the given text and return a DetectionResult describing
 * what (if anything) was found. Strategies must be stateless and side-effect free
 * — the pipeline calls them in sequence and aggregates results.
 *
 * When a strategy performs masking (e.g. PiiMaskingDetector), the sanitised text
 * is returned in DetectionResult::$processedText. The pipeline feeds that value
 * into subsequent strategies so that later detectors operate on already-masked text.
 */
interface DetectionStrategyInterface
{
	public function detect(string $text): DetectionResult;
}
