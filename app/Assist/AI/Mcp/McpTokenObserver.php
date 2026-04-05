<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Psr\Log\LoggerInterface;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

/**
 * Logs MCP tool I/O token usage to a project-local JSONL file.
 *
 * Serves two complementary purposes:
 *   1. Appends a structured JSON line per tool invocation to
 *      {@see self::LOG_FILE} for persistent, client-agnostic usage tracking.
 *   2. Forwards a human-readable summary to the injected PSR-3
 *      {@see LoggerInterface} so MCP activity appears in the framework's
 *      standard daily log files alongside all other application events.
 *
 * JSONL entry schema:
 *   - ts         : ISO-8601 timestamp
 *   - tool       : tool name (e.g. 'read_file')
 *   - in_chars   : input character count
 *   - out_chars  : output character count
 *   - in_tokens  : estimated input tokens
 *   - out_tokens : estimated output tokens
 *   - total      : in_tokens + out_tokens
 *
 * The JSONL log lives at `storage/ai/mcp-token-usage.jsonl` inside the project,
 * persisting regardless of which MCP client is used (Claude Desktop, Codex,
 * Inspector, etc.).
 *
 * @see McpTokenCalculator  Token estimation engine.
 * @see McpElements         Calls {@see log()} after every tool invocation.
 */
final class McpTokenObserver
{
	/** Relative path from storage root to the JSONL token log. */
	private const LOG_FILE = '/ai/mcp-token-usage.jsonl';

	public function __construct(
		private readonly McpTokenCalculator $calculator,
		private readonly LoggerInterface $logger,
	) {}

	/**
	 * Records a single MCP tool invocation.
	 *
	 * Estimates token counts for both input and output, appends a JSONL entry
	 * to the persistent log file, and writes a PSR-3 debug entry to the
	 * framework's daily log.
	 *
	 * @param string              $tool   tool name (e.g. 'read_file')
	 * @param array<mixed>|string $input  raw input parameters or serialised string
	 * @param array<mixed>|string $output raw tool output (text or serialisable array)
	 */
	public function log(string $tool, array|string $input, array|string $output): void
	{
		$inputStr = is_array($input) ? (string) json_encode($input, JSON_UNESCAPED_SLASHES) : $input;
		$outputStr = is_array($output) ? (string) json_encode($output, JSON_UNESCAPED_SLASHES) : $output;

		$inTokens = $this->calculator->countText($inputStr);
		$outTokens = $this->calculator->countText($outputStr);

		$entry = [
			'ts' => date('c'),
			'tool' => $tool,
			'in_chars' => mb_strlen($inputStr),
			'out_chars' => mb_strlen($outputStr),
			'in_tokens' => $inTokens,
			'out_tokens' => $outTokens,
			'total' => $inTokens + $outTokens,
		];

		$this->appendJsonl($entry);

		$this->logger->debug("MCP tool:{$tool} in:{$inTokens} out:{$outTokens} total:" . ($inTokens + $outTokens));
	}

	/**
	 * Returns a summary of today's cumulative MCP token usage.
	 *
	 * Scans the JSONL log for entries matching today's date and aggregates
	 * call count and token totals.
	 *
	 * @return array{date: string, calls: int, in_tokens: int, out_tokens: int, total_tokens: int}
	 */
	public function todaySummary(): array
	{
		$logFile = Path::getStorageDir() . self::LOG_FILE;
		$today = date('Y-m-d');

		$calls = 0;
		$inTotal = 0;
		$outTotal = 0;

		if (!is_file($logFile)) {
			return ['date' => $today, 'calls' => 0, 'in_tokens' => 0, 'out_tokens' => 0, 'total_tokens' => 0];
		}

		$handle = fopen($logFile, 'r');

		if (false === $handle) {
			return ['date' => $today, 'calls' => 0, 'in_tokens' => 0, 'out_tokens' => 0, 'total_tokens' => 0];
		}

		while (false !== ($line = fgets($handle))) {
			$line = trim($line);

			if ('' === $line) {
				continue;
			}

			/** @var null|array{ts?: string, in_tokens?: int, out_tokens?: int} $entry */
			$entry = json_decode($line, true);

			if (!is_array($entry) || !isset($entry['ts'])) {
				continue;
			}

			if (str_starts_with($entry['ts'], $today)) {
				++$calls;
				$inTotal += $entry['in_tokens'] ?? 0;
				$outTotal += $entry['out_tokens'] ?? 0;
			}
		}

		fclose($handle);

		return [
			'date' => $today,
			'calls' => $calls,
			'in_tokens' => $inTotal,
			'out_tokens' => $outTotal,
			'total_tokens' => $inTotal + $outTotal,
		];
	}

	/**
	 * Appends a single JSON entry as a line to the JSONL log file.
	 *
	 * Creates the parent directory if it does not exist.
	 *
	 * @param array<string, mixed> $entry log entry to serialise and append
	 */
	private function appendJsonl(array $entry): void
	{
		$logDir = Path::getStorageDir() . '/ai';

		if (!is_dir($logDir)) {
			mkdir($logDir, 0755, true);
		}

		$logFile = $logDir . '/mcp-token-usage.jsonl';
		File::open(Disk::Local, $logFile)->append(
			json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n"
		);
	}
}
