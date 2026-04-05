<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\McpShell;
use PHPUnit\Framework\Attributes\Test;
use System\Testing\TestCase;

/**
 * Unit tests for the static parse methods in {@see McpShell}.
 *
 * These are pure functions (no I/O, no process spawning) and can be tested
 * without the integration group exclusion. They validate the output-parsing
 * logic for PHPUnit, PHPStan, and PHP-CS-Fixer independently of shell exec.
 */
final class McpShellParseTest extends TestCase
{
	#[Test]
	public function parse_phpunit_output_detects_success(): void
	{
		$output = <<<'OUT'
PHPUnit 11.0.0
..........

Time: 00:00.123, Memory: 18.00 MB

OK (10 tests, 25 assertions)
OUT;

		$result = McpShell::parsePhpunitOutput($output);

		$this->assertTrue($result['ok']);
		$this->assertStringStartsWith('OK', $result['summary']);
	}

	#[Test]
	public function parse_phpunit_output_detects_failure(): void
	{
		$output = <<<'OUT'
PHPUnit 11.0.0
.F.

Time: 00:00.234, Memory: 20.00 MB

FAILURES!
Tests: 3, Assertions: 5, Failures: 1.
OUT;

		$result = McpShell::parsePhpunitOutput($output);

		$this->assertFalse($result['ok']);
		$this->assertNotEmpty($result['summary']);
	}

	#[Test]
	public function parse_phpunit_output_detects_errors(): void
	{
		$output = "ERRORS!\nTests: 1, Assertions: 0, Errors: 1.";
		$result = McpShell::parsePhpunitOutput($output);

		$this->assertFalse($result['ok']);
	}

	#[Test]
	public function parse_phpunit_output_handles_empty(): void
	{
		$result = McpShell::parsePhpunitOutput('');

		$this->assertFalse($result['ok']);
		$this->assertSame('', $result['summary']);
	}

	#[Test]
	public function parse_phpstan_detects_no_errors(): void
	{
		$this->assertTrue(McpShell::parsePhpstanOutput('[OK] No errors'));
	}

	#[Test]
	public function parse_phpstan_detects_errors(): void
	{
		$output = 'app/Foo.php:10: Error Parameter #1 is wrong.';
		$this->assertFalse(McpShell::parsePhpstanOutput($output));
	}

	#[Test]
	public function parse_phpstan_detects_fatal(): void
	{
		$this->assertFalse(McpShell::parsePhpstanOutput('Fatal error: Allowed memory size exhausted'));
	}

	#[Test]
	public function parse_cs_fixer_detects_pass(): void
	{
		$this->assertTrue(McpShell::parseCsFixerOutput('Fixed all files in 0.123 seconds.'));
	}

	#[Test]
	public function parse_cs_fixer_detects_failure_on_fail(): void
	{
		$this->assertFalse(McpShell::parseCsFixerOutput('FAIL some/File.php'));
	}

	#[Test]
	public function parse_cs_fixer_detects_failure_on_needs_fixing(): void
	{
		$output = "Files that need fixing:\n  src/Foo.php";
		$this->assertFalse(McpShell::parseCsFixerOutput($output));
	}

	#[Test]
	public function parse_clover_returns_empty_for_missing_file(): void
	{
		$result = McpShell::parseCloverXml('/nonexistent/path.xml');

		$this->assertSame([], $result['stats']);
		$this->assertSame([], $result['files']);
	}

	#[Test]
	public function parse_clover_extracts_stats_from_valid_xml(): void
	{
		$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1234567890">
  <project timestamp="1234567890">
    <metrics files="2" loc="100" ncloc="90"
             classes="2" coveredclasses="1"
             methods="10" coveredmethods="8"
             statements="50" coveredstatements="40"/>
    <file name="/var/www/html/app/Foo.php">
      <metrics statements="30" coveredstatements="25"
               methods="5" coveredmethods="4"
               classes="1" coveredclasses="1"/>
      <line num="10" type="stmt" count="0"/>
      <line num="15" type="stmt" count="3"/>
    </file>
    <file name="/var/www/html/app/Bar.php">
      <metrics statements="20" coveredstatements="15"
               methods="5" coveredmethods="4"
               classes="1" coveredclasses="0"/>
      <line num="5" type="stmt" count="0"/>
      <line num="8" type="stmt" count="0"/>
    </file>
  </project>
</coverage>
XML;

		$tmpFile = tempnam(sys_get_temp_dir(), 'clover_') . '.xml';
		file_put_contents($tmpFile, $xml);

		try {
			$result = McpShell::parseCloverXml($tmpFile);

			$this->assertNotEmpty($result['stats']);
			$this->assertSame(80.0, $result['stats']['lines_pct']);
			$this->assertSame(40, $result['stats']['lines_covered']);
			$this->assertSame(50, $result['stats']['lines_total']);

			$this->assertCount(2, $result['files']);

			/** @var array{file: string, uncovered_lines: int[]} $worstFile */
			$worstFile = $result['files'][0];
			$this->assertStringContainsString('Bar', $worstFile['file']);
			$this->assertSame([5, 8], $worstFile['uncovered_lines']);
		} finally {
			unlink($tmpFile);
		}
	}
}
