<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Finder;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Finder\Makefile;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Tests\TestCase;

final class MakefileTest extends TestCase
{
	private string $tempDir;
	private string $makefilePath;

	protected function setUp(): void
	{
		parent::setUp();

		$this->tempDir = Path::getTempTestDir() . '/makefile_' . uniqid();
		File::open(Disk::Local, $this->tempDir)->create(true);

		$this->makefilePath = $this->tempDir . '/Makefile';
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		File::open(Disk::Local, $this->tempDir)->remove();
	}

	#[Test]
	public function parses_targets_with_recipes(): void
	{
		file_put_contents($this->makefilePath, implode("\n", [
			'.PHONY: build',
			'build:',
			"\t@echo building",
			"\t@make compile",
			'',
			'.PHONY: test',
			'test:',
			"\t@./vendor/bin/phpunit",
		]));

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertSame(['echo building', 'make compile'], $targets['build']);
		$this->assertSame(['./vendor/bin/phpunit'], $targets['test']);
	}

	#[Test]
	public function excludes_phony_declarations(): void
	{
		file_put_contents($this->makefilePath, implode("\n", [
			'.PHONY: lint',
			'lint:',
			"\t@php-cs-fixer fix .",
		]));

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertArrayNotHasKey('.PHONY', $targets);
	}

	#[Test]
	public function handles_target_with_no_recipes(): void
	{
		file_put_contents($this->makefilePath, implode("\n", [
			'clean:',
			'',
			'build:',
			"\t@echo build",
		]));

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertSame([], $targets['clean']);
		$this->assertSame(['echo build'], $targets['build']);
	}

	#[Test]
	public function handles_empty_makefile(): void
	{
		file_put_contents($this->makefilePath, '');

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertSame([], $targets);
	}

	#[Test]
	public function strips_at_prefix_from_recipes(): void
	{
		file_put_contents($this->makefilePath, implode("\n", [
			'deploy:',
			"\t@rsync -av . server:/app",
		]));

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertSame(['rsync -av . server:/app'], $targets['deploy']);
	}

	#[Test]
	public function parses_hyphenated_target_names(): void
	{
		file_put_contents($this->makefilePath, implode("\n", [
			'cache-clear:',
			"\t@rm -rf cache/",
		]));

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertArrayHasKey('cache-clear', $targets);
	}

	#[Test]
	public function parses_underscored_target_names(): void
	{
		file_put_contents($this->makefilePath, implode("\n", [
			'run_tests:',
			"\t@phpunit",
		]));

		$targets = (new Makefile($this->makefilePath))->getTargets();

		$this->assertArrayHasKey('run_tests', $targets);
	}

	#[Test]
	public function makefile_not_found(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new Makefile($this->tempDir . '/NonExistent');
	}
}
