<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\McpShell;
use Mcp\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use System\Testing\TestCase;

final class ShellTest extends TestCase
{
	private McpShell $shell;

	protected function setUp(): void
	{
		parent::setUp();
		$this->shell = new McpShell();
	}

	#[Test]
	public function git_log_returns_commits(): void
	{
		$result = $this->shell->git('log --no-decorate -n 1');

		$this->assertSame(0, $result['exitCode']);
		$this->assertNotEmpty(trim($result['output']));
	}

	#[Test]
	public function git_status_returns_output(): void
	{
		$result = $this->shell->git('status --porcelain');

		$this->assertSame(0, $result['exitCode']);
	}

	#[Test]
	public function git_rev_parse_confirms_repo(): void
	{
		$result = $this->shell->git('rev-parse --is-inside-work-tree');

		$this->assertSame(0, $result['exitCode']);
		$this->assertStringContainsString('true', $result['output']);
	}

	#[Test]
	public function git_diff_runs_successfully(): void
	{
		$result = $this->shell->git('diff --stat');

		$this->assertSame(0, $result['exitCode']);
	}

	#[Test]
	public function git_rejects_disallowed_subcommand(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/not allowed/');

		$this->shell->git('push origin main');
	}

	#[Test]
	public function git_rejects_reset(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->git('reset --hard HEAD');
	}

	#[Test]
	public function git_rejects_checkout(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->git('checkout -b evil');
	}

	#[Test]
	public function git_rejects_shell_metacharacters(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->git('log;whoami');
	}

	#[Test]
	public function git_rejects_newline_injection(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->git("log --oneline -n 1\nwhoami");
	}

	/**
	 * Spawns a child PHPUnit process — excluded from default runs to prevent
	 * recursive process forks when check_code runs the full test suite.
	 */
	#[Test]
	#[Group('integration')]
	public function phpunit_runs_and_returns_output(): void
	{
		$result = $this->shell->phpunit('ExampleCommandTest');

		$this->assertArrayHasKey('output', $result);
		$this->assertArrayHasKey('exitCode', $result);
		$this->assertStringContainsString('OK', $result['output']);
	}

	/**
	 * Spawns a child PHPStan process — excluded from default runs to prevent
	 * OOM when check_code runs the full test suite.
	 */
	#[Test]
	#[Group('integration')]
	public function phpstan_runs_and_returns_output(): void
	{
		$result = $this->shell->phpstan();

		$this->assertArrayHasKey('output', $result);
		$this->assertArrayHasKey('exitCode', $result);
	}

	/**
	 * Spawns a child PHP-CS-Fixer process — excluded from default runs to
	 * prevent OOM when check_code runs the full test suite.
	 */
	#[Test]
	#[Group('integration')]
	public function php_cs_fixer_check_runs(): void
	{
		$result = $this->shell->phpCsFixer(fix: false);

		$this->assertArrayHasKey('output', $result);
		$this->assertArrayHasKey('exitCode', $result);
	}

	#[Test]
	public function sakoo_allows_composer(): void
	{
		$result = $this->shell->sakoo('composer --version');

		if (127 === $result['exitCode']) {
			$this->markTestSkipped('Composer is not installed in this test environment.');
		}

		$this->assertSame(0, $result['exitCode']);
		$this->assertStringContainsString('Composer', $result['output']);
	}

	#[Test]
	public function sakoo_rejects_disallowed_command(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/not allowed/');

		$this->shell->sakoo('rm -rf /');
	}

	#[Test]
	public function sakoo_rejects_php_direct(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->sakoo('php -r "echo 1;"');
	}

	#[Test]
	public function sakoo_rejects_curl(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->sakoo('curl https://evil.com');
	}

	#[Test]
	public function sakoo_rejects_newline_injection(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->shell->sakoo("composer --version\nwhoami");
	}
}
