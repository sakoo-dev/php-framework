<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Console;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Console\Commands\HelpCommand;
use Sakoo\Framework\Core\Console\Commands\NotFoundCommand;
use Sakoo\Framework\Core\Console\Commands\VersionCommand;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Tests\TestCase;

final class BuiltinCommandsTest extends TestCase
{
	#[Test]
	public function not_found_command_get_name(): void
	{
		$this->assertSame('not-found', NotFoundCommand::getName());
	}

	#[Test]
	public function not_found_command_get_description(): void
	{
		$this->assertStringContainsString('not found', NotFoundCommand::getDescription());
	}

	#[Test]
	public function not_found_command_run_returns_error(): void
	{
		$output = new Output(false);
		$output->setSilentMode();

		$this->assertSame(Output::ERROR, (new NotFoundCommand())->run(new Input([]), $output));
		$this->assertStringContainsString('Requested command has not found.', $output->getDisplay());
		$this->assertStringContainsString('try "./sakoo assist help"', $output->getDisplay());
	}

	#[Test]
	public function version_command_get_name(): void
	{
		$this->assertSame('version', VersionCommand::getName());
	}

	#[Test]
	public function version_command_get_description(): void
	{
		$this->assertNotEmpty(VersionCommand::getDescription());
	}

	#[Test]
	public function help_command_get_name(): void
	{
		$this->assertSame('help', HelpCommand::getName());
	}

	#[Test]
	public function help_command_get_description(): void
	{
		$this->assertNotEmpty(HelpCommand::getDescription());
	}
}
