<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\McpTokenCalculator;
use App\Assist\AI\Mcp\McpTokenObserver;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use System\Path\Path;
use System\Testing\TestCase;

/**
 * Unit tests for {@see McpTokenObserver}.
 *
 * Validates JSONL logging, today-summary aggregation, and graceful
 * handling of missing/empty log files.
 */
final class McpTokenObserverTest extends TestCase
{
	private McpTokenObserver $observer;
	private string $logDir;
	private string $logFile;

	protected function setUp(): void
	{
		parent::setUp();

		$this->observer = new McpTokenObserver(
			new McpTokenCalculator(),
			new NullLogger(),
		);

		$storageDir = Path::getStorageDir();
		$this->logDir = $storageDir . '/ai';
		$this->logFile = $this->logDir . '/mcp-token-usage.jsonl';
	}

	#[Test]
	public function log_appends_jsonl_entry(): void
	{
		$sizeBefore = is_file($this->logFile) ? filesize($this->logFile) : 0;

		$this->observer->log('test_tool', ['key' => 'value'], 'some output');

		clearstatcache(true, $this->logFile);
		$this->assertFileExists($this->logFile);
		$this->assertGreaterThan($sizeBefore, filesize($this->logFile));

		/** @var string[] $lines */
		$lines = array_filter(file($this->logFile) ?: []);
		$lastLine = end($lines);
		$this->assertIsString($lastLine);

		/** @var array{tool: string, ts: string, in_chars: int, out_chars: int, in_tokens: int, out_tokens: int, total: int} $entry */
		$entry = json_decode(trim($lastLine), true);

		$this->assertSame('test_tool', $entry['tool']);
		$this->assertArrayHasKey('ts', $entry);
		$this->assertArrayHasKey('in_chars', $entry);
		$this->assertArrayHasKey('out_chars', $entry);
		$this->assertArrayHasKey('in_tokens', $entry);
		$this->assertArrayHasKey('out_tokens', $entry);
		$this->assertArrayHasKey('total', $entry);
		$this->assertSame($entry['in_tokens'] + $entry['out_tokens'], $entry['total']);
	}

	#[Test]
	public function log_handles_array_input_and_output(): void
	{
		$this->observer->log('array_tool', ['a' => 1, 'b' => 2], ['result' => 'ok']);

		/** @var string[] $lines */
		$lines = array_filter(file($this->logFile) ?: []);
		$lastLine = end($lines);
		$this->assertIsString($lastLine);

		/** @var array{tool: string, in_chars: int, out_chars: int} $entry */
		$entry = json_decode(trim($lastLine), true);

		$this->assertSame('array_tool', $entry['tool']);
		$this->assertGreaterThan(0, $entry['in_chars']);
		$this->assertGreaterThan(0, $entry['out_chars']);
	}

	#[Test]
	public function today_summary_returns_valid_structure(): void
	{
		$this->observer->log('summary_test', 'input', 'output');

		$summary = $this->observer->todaySummary();

		$this->assertSame(date('Y-m-d'), $summary['date']);
		$this->assertGreaterThanOrEqual(1, $summary['calls']);
		$this->assertGreaterThanOrEqual(0, $summary['in_tokens']);
		$this->assertGreaterThanOrEqual(0, $summary['out_tokens']);
		$this->assertSame($summary['in_tokens'] + $summary['out_tokens'], $summary['total_tokens']);
	}

	#[Test]
	public function today_summary_returns_zeros_when_no_log_file(): void
	{
		$renamed = false;
		$backupPath = $this->logFile . '.bak';

		if (is_file($this->logFile)) {
			rename($this->logFile, $backupPath);
			$renamed = true;
		}

		try {
			$summary = $this->observer->todaySummary();

			$this->assertSame(0, $summary['calls']);
			$this->assertSame(0, $summary['in_tokens']);
			$this->assertSame(0, $summary['out_tokens']);
			$this->assertSame(0, $summary['total_tokens']);
		} finally {
			if ($renamed) {
				rename($backupPath, $this->logFile);
			}
		}
	}
}
