<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\DevCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

final class DevCommandTest extends AbstractCommandBase
{
	private Command $command;

	protected function setUp(): void
	{
		parent::setUp();
		$this->command = new DevCommand();
	}

	protected function getCommand(): Command
	{
		return $this->command;
	}

	#[Test]
	public function command_works_properly(): void
	{
		$input = new Input(['dev']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString('JIT Enabled:', $output->getDisplay());
	}

	#[Test]
	public function get_name_returns_dev(): void
	{
		$this->assertSame('dev', DevCommand::getName());
	}

	#[Test]
	public function get_description_returns_string(): void
	{
		$this->assertSame('Useful Information for Developer', DevCommand::getDescription());
	}

	#[Test]
	public function run_always_returns_success(): void
	{
		$output = new Output(false);
		$output->setSilentMode();

		$this->assertSame(Output::SUCCESS, $this->command->run(new Input([]), $output));
		$this->assertStringContainsString('JIT Enabled:', $output->getDisplay());
	}
}
