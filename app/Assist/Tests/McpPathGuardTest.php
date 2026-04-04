<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\McpPathGuard;
use Mcp\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use System\Testing\TestCase;

final class McpPathGuardTest extends TestCase
{
	#[Test]
	public function guard_accepts_relative_path_inside_project(): void
	{
		$result = McpPathGuard::guard('composer.json');

		$this->assertStringEndsWith('/composer.json', $result);
	}

	#[Test]
	public function guard_accepts_absolute_path_inside_project(): void
	{
		$root = (string) getcwd();
		$result = McpPathGuard::guard($root . '/composer.json');

		$this->assertSame($root . '/composer.json', $result);
	}

	#[Test]
	public function guard_rejects_path_traversal_outside_project(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Path escapes project scope/');

		McpPathGuard::guard('../../etc/passwd');
	}

	#[Test]
	public function guard_rejects_absolute_path_outside_project(): void
	{
		$this->expectException(InvalidArgumentException::class);

		McpPathGuard::guard('/etc/passwd');
	}

	#[Test]
	public function guard_many_validates_all_paths(): void
	{
		$result = McpPathGuard::guardMany(['composer.json', 'Makefile']);

		$this->assertCount(2, $result);

		foreach ($result as $path) {
			/** @var non-empty-string $root */
			$root = (string) getcwd();
			$this->assertStringStartsWith($root, $path);
		}
	}

	#[Test]
	public function guard_many_throws_on_first_invalid_path(): void
	{
		$this->expectException(InvalidArgumentException::class);

		McpPathGuard::guardMany(['composer.json', '/etc/passwd']);
	}

	#[Test]
	public function guard_normalises_dot_segments(): void
	{
		$result = McpPathGuard::guard('app/../composer.json');

		$this->assertStringEndsWith('/composer.json', $result);
		$this->assertStringNotContainsString('..', $result);
	}
}
