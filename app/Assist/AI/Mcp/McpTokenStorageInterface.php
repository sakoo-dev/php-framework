<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

/**
 * Port for persisting MCP tool-call token entries. Mirrors the MetricStorage
 * adapter pattern so the storage backend can be swapped (JSONL → DB, etc.)
 * without touching McpTokenObserver.
 */
interface McpTokenStorageInterface
{
	public function store(McpTokenEntry $entry): void;
}
