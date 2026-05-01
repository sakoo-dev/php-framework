<?php

declare(strict_types=1);

namespace App\Assist\AI\Metric\Storage;

use App\Assist\AI\Metric\MetricEntry;
use App\Assist\AI\Metric\MetricStorageInterface;
use System\Path\Path;

/**
 * Writes one JSON object per line to a daily file at
 * storage/ai/metrics/{YYYY-MM-DD}.jsonl.
 *
 * Parent directories are created on first write. The adapter never throws on a
 * missing file — it creates it. To swap to a database or other sink, implement
 * MetricStorageInterface and rebind it in AIServiceLoader.
 */
final class JsonlMetricStorage implements MetricStorageInterface
{
	public function store(MetricEntry $entry): void
	{
		$dir = Path::getStorageDir() . '/ai/metrics';
		$file = $dir . '/' . date('Y-m-d') . '.jsonl';

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		file_put_contents(
			$file,
			json_encode($entry->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
			FILE_APPEND | LOCK_EX,
		);
	}
}
