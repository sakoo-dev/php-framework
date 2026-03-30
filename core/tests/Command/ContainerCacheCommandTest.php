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

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString('Container cache created successfully.', $output->getDisplay());
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

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString('Container cache cleared successfully.', $output->getDisplay());
	}

	#[Test]
	public function get_name_returns_container_cache(): void
	{
		$this->assertSame('container:cache', ContainerCacheCommand::getName());
	}

	#[Test]
	public function get_description_returns_string(): void
	{
		$this->assertSame('Creates container cache for better performance', ContainerCacheCommand::getDescription());
	}
}
