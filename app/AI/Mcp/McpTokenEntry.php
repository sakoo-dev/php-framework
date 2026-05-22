<?php

declare(strict_types=1);

namespace App\AI\Mcp;

/**
 * Immutable record of a single MCP tool call, capturing both raw character
 * counts and estimated token counts for budget tracking and dashboards.
 */
final readonly class McpTokenEntry
{
	public function __construct(
		public string $timestamp,
		public string $tool,
		public int $inChars,
		public int $outChars,
		public int $inTokens,
		public int $outTokens,
	) {}

	/** @return array<string, mixed> */
	public function toArray(): array
	{
		return [
			'ts' => $this->timestamp,
			'tool' => $this->tool,
			'in_chars' => $this->inChars,
			'out_chars' => $this->outChars,
			'in_tokens' => $this->inTokens,
			'out_tokens' => $this->outTokens,
			'total' => $this->inTokens + $this->outTokens,
		];
	}
}
