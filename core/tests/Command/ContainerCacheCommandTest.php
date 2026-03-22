<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\ContainerCacheCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Container\Container;

final class ContainerCacheCommandTest extends AbstractCommandBase
{
	private Container $container;
	private Command $command;

	protected function setUp(): void
	{
		parent::setUp();

		$this->container = $this->createMock(Container::class);
		$this->command = new ContainerCacheCommand($this->container);
	}

	protected function getCommand(): Command
	{
		return $this->command;
	}

	#[Test]
	public function command_creates_cache_properly(): void
	{
		$input = new Input(['container:cache']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$this->container->expects($this->once())->method('dumpCache');

		$status = $console->run();
		$result = $output->getDisplay();

		$this->assertEquals(Output::SUCCESS, $status);
		$this->assertStringContainsString('Container cache created successfully.', $result);
	}

	#[Test]
	public function command_clears_cache_properly(): void
	{
		$input = new Input(['container:cache', '--clear']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$this->container->expects($this->once())->method('flushCache');

		$status = $console->run();
		$result = $output->getDisplay();

		$this->assertEquals(Output::SUCCESS, $status);
		$this->assertStringContainsString('Container cache cleared successfully.', $result);
	}
}
