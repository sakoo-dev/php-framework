<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\FileSystem\Local;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\Finder\FileFinder;
use Sakoo\Framework\Core\Tests\TestCase;

final class PathGuardTest extends TestCase
{
	#[Test]
	public function guard_accepts_relative_path_inside_project(): void
	{
		$result = FileFinder::guard('composer.json');

		$this->assertStringEndsWith('/composer.json', $result);
	}

	#[Test]
	public function guard_accepts_absolute_path_inside_project(): void
	{
		$root = (string) getcwd();
		$result = FileFinder::guard($root . '/composer.json');

		$this->assertSame($root . '/composer.json', $result);
	}

	#[Test]
	public function guard_rejects_path_traversal_outside_project(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Path escapes project scope/');

		FileFinder::guard('../../etc/passwd');
	}

	#[Test]
	public function guard_rejects_absolute_path_outside_project(): void
	{
		$this->expectException(InvalidArgumentException::class);

		FileFinder::guard('/etc/passwd');
	}

	#[Test]
	public function guard_many_validates_all_paths(): void
	{
		$result = FileFinder::guardMany(['composer.json', 'Makefile']);

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

		FileFinder::guardMany(['composer.json', '/etc/passwd']);
	}

	#[Test]
	public function guard_normalises_dot_segments(): void
	{
		$result = FileFinder::guard('app/../composer.json');

		$this->assertStringEndsWith('/composer.json', $result);
		$this->assertStringNotContainsString('..', $result);
	}

	#[Test]
	public function guard_rejects_symlink_escape_for_new_file_targets(): void
	{
		$root = (string) getcwd();
		$linkPath = $root . '/path_guard_link_' . uniqid();
		$outside = sys_get_temp_dir();

		if (!function_exists('symlink') || !@symlink($outside, $linkPath)) {
			$this->markTestSkipped('Cannot create symlink in this environment.');
		}

		try {
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessageMatches('/Path escapes project scope/');

			FileFinder::guard(basename($linkPath) . '/future-file.txt');
		} finally {
			if (is_link($linkPath)) {
				unlink($linkPath);
			}
		}
	}
}
