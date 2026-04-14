<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use System\Path\Path;

final class McpTokenObserver
{
	private const MCP_LOG_FILE = '/ai/mcp-token-usage.jsonl';
	private const AGENT_LOG_FILE = '/ai/agent-token-usage.jsonl';

	public function __construct(
		private readonly McpTokenCalculator $calculator,
	) {}

	/**
	 * @param array<mixed>|string $input  raw input parameters or serialised string
	 * @param array<mixed>|string $output raw tool output (text or serialisable array)
	 */
	public function log(string $tool, array|string $input, array|string $output): void
	{
		$inputStr = is_array($input) ? (string) json_encode($input, JSON_UNESCAPED_SLASHES) : $input;
		$outputStr = is_array($output) ? (string) json_encode($output, JSON_UNESCAPED_SLASHES) : $output;

		$inTokens = $this->calculator->countText($inputStr);
		$outTokens = $this->calculator->countText($outputStr);

		$this->appendJsonl(self::MCP_LOG_FILE, [
			'ts' => date('c'),
			'tool' => $tool,
			'in_chars' => mb_strlen($inputStr),
			'out_chars' => mb_strlen($outputStr),
			'in_tokens' => $inTokens,
			'out_tokens' => $outTokens,
			'total' => $inTokens + $outTokens,
		]);
	}

	public function logAgent(string $agent, int|string $inTokens, int|string $outTokens, int|string $total): void
	{
		$this->appendJsonl(self::AGENT_LOG_FILE, [
			'ts' => date('c'),
			'agent' => $agent,
			'in_tokens' => $inTokens,
			'out_tokens' => $outTokens,
			'total' => $total,
		]);
	}

	/** @return array{date: string, calls: int, in_tokens: int, out_tokens: int, total_tokens: int} */
	public function todayMcpSummary(): array
	{
		return $this->buildDailySummary(Path::getStorageDir() . self::MCP_LOG_FILE);
	}

	/** @return array{date: string, calls: int, in_tokens: int, out_tokens: int, total_tokens: int} */
	public function todayAgentSummary(): array
	{
		return $this->buildDailySummary(Path::getStorageDir() . self::AGENT_LOG_FILE);
	}

	/** @return array{date: string, calls: int, in_tokens: int, out_tokens: int, total_tokens: int} */
	private function buildDailySummary(string $logFile): array
	{
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

			/** @var null|array{ts?: string, in_tokens?: null|int, out_tokens?: null|int} $entry */
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
	 * @param array<string, mixed> $entry log entry to serialise and append
	 */
	private function appendJsonl(string $relativePath, array $entry): void
	{
		$logDir = Path::getStorageDir() . '/ai';
		$logFile = Path::getStorageDir() . $relativePath;

		if (!is_dir($logDir)) {
			mkdir($logDir, 0755, true);
		}

		File::open(Disk::Local, $logFile)->append(
			json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n"
		);
	}
}
