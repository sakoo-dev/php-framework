<?php

declare(strict_types=1);

namespace App\AI\Metric;

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
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
		$path = Path::getStorageDir() . '/ai/metrics/' . date('Y-m-d') . '.jsonl';
		$file = File::open(Disk::Local, $path);
		$file->append(json_encode($entry->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
	}
}
