<?php

declare(strict_types=1);

namespace App\AI\Metric;

/**
 * Port for persisting metric entries. Implement this interface to switch the
 * storage backend from JSONL files to a database, time-series store, or any
 * other sink without touching the observer or agent code.
 */
interface MetricStorageInterface
{
	public function store(MetricEntry $entry): void;
}
