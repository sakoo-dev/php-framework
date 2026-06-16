<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer\Strategy;

use App\AI\Neuron\Optimizer\ToolResultPruningStrategyInterface;

/**
 * Keeps only the N most recent tool results based on timestamp.
 * If timestamps are missing, results are kept in original order (FIFO).
 */
final readonly class RecentToolResultsPruningStrategy implements ToolResultPruningStrategyInterface
{
	public function __construct(private int $keepCount = 10) {}

	public function prune(array $toolResults): array
	{
		if (count($toolResults) <= $this->keepCount) {
			return $toolResults;
		}

		$withTimestamps = array_filter(
			$toolResults,
			static fn (array $r): bool => is_int($r['timestamp'] ?? null) && $r['timestamp'] > 0
		);

		if (count($withTimestamps) < 2) {
			return array_slice($toolResults, -$this->keepCount);
		}

		usort($toolResults, static function (array $a, array $b): int {
			$timestampA = is_int($a['timestamp'] ?? null) ? $a['timestamp'] : 0;
			$timestampB = is_int($b['timestamp'] ?? null) ? $b['timestamp'] : 0;

			return $timestampB <=> $timestampA;
		});

		return array_slice($toolResults, 0, $this->keepCount);
	}
}
