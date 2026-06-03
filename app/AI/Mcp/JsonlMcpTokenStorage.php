<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

/**
 * Appends one JSON object per line to a single flat file at
 * storage/ai/mcp-token-usage.jsonl, matching the original McpTokenObserver
 * behaviour. Swap this for a daily-file or database adapter by binding a
 * different McpTokenStorageInterface in AIServiceLoader.
 */
final class JsonlMcpTokenStorage implements McpTokenStorageInterface
{
	public function store(McpTokenEntry $entry): void
	{
		$path = Path::getStorageDir() . '/ai/mcp-token-usage.jsonl';
		$file = File::open(Disk::Local, $path);
		$file->append((json_encode($entry->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '') . PHP_EOL);
	}
}
