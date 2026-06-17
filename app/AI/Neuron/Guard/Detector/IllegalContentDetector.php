<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard\Detector;

use App\AI\Neuron\Guard\ContentClassification;
use App\AI\Neuron\Guard\Dataset\PatternDatasetInterface;
use App\AI\Neuron\Guard\DetectionResult;
use App\AI\Neuron\Guard\DetectionStrategyInterface;

/**
 * Detects unethical content using patterns from a PatternDatasetInterface.
 *
 * Runs every pattern in the dataset and accumulates all matching reasons.
 * All matches are collected before returning so the result reflects every
 * triggered pattern rather than stopping at the first.
 */
final class IllegalContentDetector implements DetectionStrategyInterface
{
	/** @param array<string,string> $dataset */
	public function __construct(private array $dataset) {}

	public function detect(string $text): DetectionResult
	{
		$reasons = [];

		foreach ($this->dataset as $pattern => $reason) {
			if (1 === preg_match("/$pattern/u", $text)) {
				$reasons[] = 'Illegal content: ' . $reason;
			}
		}

		if ([] === $reasons) {
			return DetectionResult::clean($text);
		}

		$result = DetectionResult::flag(ContentClassification::Restricted, array_shift($reasons), $text);

		foreach ($reasons as $reason) {
			$result = $result->withReason(ContentClassification::Restricted, $reason);
		}

		return $result;
	}
}
