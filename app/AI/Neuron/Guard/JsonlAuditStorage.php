<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

/**
 * Writes one JSON object per line to a daily audit file at
 * storage/ai/audit/{YYYY-MM-DD}.jsonl.
 *
 * Follows the same creation-on-write pattern as JsonlMetricStorage — parent
 * directories are created automatically; no exception is thrown for a missing file.
 */
final class JsonlAuditStorage implements AuditStorageInterface
{
	public function store(AuditEntry $entry): void
	{
		$path = Path::getStorageDir() . '/ai/audit/' . date('Y-m-d') . '.jsonl';
		$file = File::open(Disk::Local, $path);
		$file->create();
		$file->append((json_encode($entry->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '') . PHP_EOL);
	}
}
