<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\Commands\ExampleCommand;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Tests\Command\AbstractCommandBase;

final class ExampleCommandTest extends AbstractCommandBase
{
	private Command $command;

	protected function setUp(): void
	{
		AbstractCommandBase::setUp();
		$this->command = new ExampleCommand();
	}

	protected function getCommand(): Command
	{
		return $this->command;
	}

	#[Test]
	public function command_works_properly(): void
	{
		$input = new Input(['example']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$status = $console->run();
		$result = $output->getDisplay();

		$this->assertEquals(Output::SUCCESS, $status);
		$this->assertStringContainsString('It works properly', $result);
	}
}
