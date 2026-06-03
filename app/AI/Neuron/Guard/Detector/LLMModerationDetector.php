<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard\Detector;

use App\AI\Neuron\Guard\Dataset\ModerationCategoryDatasetInterface;
use App\AI\Neuron\Guard\DetectionResult;
use App\AI\Neuron\Guard\DetectionStrategyInterface;
use App\AI\Neuron\Provider\ModerationProvider;
use NeuronAI\Exceptions\HttpException;

/**
 * Delegates content moderation to the OpenAI Moderation API.
 *
 * Category-to-classification mapping is owned by ModerationCategoryDatasetInterface.
 * All flagged categories are accumulated as individual reasons in DetectionResult.
 * A network failure or API error never blocks inference — returns clean on error.
 */
final class LLMModerationDetector implements DetectionStrategyInterface
{
	public function __construct(private ModerationProvider $moderation) {}

	public function detect(string $text): DetectionResult
	{
		try {
			return $this->moderation->call($text);
		} catch (HttpException) {
			return DetectionResult::clean($text);
		}
	}
}
