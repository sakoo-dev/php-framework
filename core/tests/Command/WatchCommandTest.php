<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\Watcher\WatchCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Watcher\Watcher;

final class WatchCommandTest extends AbstractCommandBase
{
	private Command $command;
	private Watcher $watcher;

	protected function setUp(): void
	{
		parent::setUp();

		$this->watcher = $this->createMock(Watcher::class);
		$this->command = new WatchCommand($this->watcher);
	}

	protected function getCommand(): Command
	{
		return $this->command;
	}

	#[Test]
	public function command_works_properly(): void
	{
		$input = new Input(['watch']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$this->watcher->expects($this->once())->method('run');

		$status = $console->run();
		$result = $output->getDisplay();

		$this->assertEquals(Output::SUCCESS, $status);
		$this->assertStringContainsString('Watching ...', $result);
	}
}
