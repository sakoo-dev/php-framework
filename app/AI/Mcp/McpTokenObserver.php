<?php

declare(strict_types=1);

namespace App\AI\Mcp;

/**
 * Records token usage for every MCP tool call and exposes a daily summary.
 *
 * Storage is fully decoupled through McpTokenStorageInterface — swap the
 * adapter in AIServiceLoader to change the persistence backend without touching
 * this class.
 */
final class McpTokenObserver
{
	public function __construct(
		private readonly McpTokenCalculator $calculator,
		private readonly McpTokenStorageInterface $storage,
	) {}

	/**
	 * @param array<mixed>|string $input raw input parameters or serialised string
	 * @param array<mixed>|string $output raw tool output (text or serialisable array)
	 */
	public function log(string $tool, array|string $input, array|string $output): void
	{
		$inputStr = is_array($input) ? (string) json_encode($input, JSON_UNESCAPED_SLASHES) : $input;
		$outputStr = is_array($output) ? (string) json_encode($output, JSON_UNESCAPED_SLASHES) : $output;

		$inTokens = $this->calculator->countText($inputStr);
		$outTokens = $this->calculator->countText($outputStr);

		$this->storage->store(new McpTokenEntry(
			timestamp: date('c'),
			tool: $tool,
			inChars: mb_strlen($inputStr),
			outChars: mb_strlen($outputStr),
			inTokens: $inTokens,
			outTokens: $outTokens,
		));
	}
}
