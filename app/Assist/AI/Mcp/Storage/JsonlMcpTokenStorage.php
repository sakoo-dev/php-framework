<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Storage;

use App\Assist\AI\Mcp\McpTokenEntry;
use App\Assist\AI\Mcp\McpTokenStorageInterface;
use System\Path\Path;

/**
 * Appends one JSON object per line to a single flat file at
 * storage/ai/mcp-token-usage.jsonl, matching the original McpTokenObserver
 * behaviour. Swap this for a daily-file or database adapter by binding a
 * different McpTokenStorageInterface in AIServiceLoader.
 */
final class JsonlMcpTokenStorage implements McpTokenStorageInterface
{
	private const RELATIVE_PATH = '/ai/mcp-token-usage.jsonl';

	public function store(McpTokenEntry $entry): void
	{
		$dir = Path::getStorageDir() . '/ai';
		$file = Path::getStorageDir() . self::RELATIVE_PATH;

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
