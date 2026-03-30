<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Console;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\ZenCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Commands\VersionCommand;
use Sakoo\Framework\Core\Console\Exceptions\CommandNotFoundException;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Constants;
use Sakoo\Framework\Core\Tests\TestCase;

final class ApplicationTest extends TestCase
{
	#[DataProvider('versionArgsProvider')]
	#[Test]
	public function it_loads_console_properly($arg): void
	{
		$input = new Input([$arg]);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString(Constants::FRAMEWORK_NAME . ' - Version: ' . Constants::FRAMEWORK_VERSION, $output->getDisplay());
	}

	public static function versionArgsProvider(): \Generator
	{
		yield ['version'];
		yield ['-version'];
		yield ['--version'];
		yield ['-v'];
		yield ['--v'];
	}

	#[Test]
	public function it_loads_help_command_properly(): void
	{
		$input = new Input(['help']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand(new VersionCommand());

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString('Available commands:', $output->getDisplay());
		$this->assertStringContainsString(VersionCommand::getName(), $output->getDisplay());
	}

	#[DataProvider('helpArgsProvider')]
	#[Test]
	public function it_loads_help_switch_properly($arg): void
	{
		$input = new Input([$arg]);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString('this command helps the user to interact with the current application', $output->getDisplay());
	}

	public static function helpArgsProvider(): \Generator
	{
		yield ['-help'];
		yield ['--help'];
		yield ['-h'];
		yield ['--h'];
	}

	#[Test]
	public function it_loads_default_command_properly(): void
	{
		$input = new Input([]);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand(resolve(ZenCommand::class));
		$console->setDefaultCommand(ZenCommand::class);

		$this->assertEquals(Output::SUCCESS, $console->run());
		$this->assertStringContainsString(Constants::FRAMEWORK_NAME . ' (Version: ' . Constants::FRAMEWORK_VERSION . ')', $output->getDisplay());
		$this->assertStringContainsString('Copyright ' . date('Y') . ' by ' . Constants::MAINTAINER, $output->getDisplay());
	}

	#[Test]
	public function it_loads_not_found_command_properly(): void
	{
		$input = new Input(['Something']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);

		$this->assertEquals(Output::ERROR, $console->run());
		$this->assertStringContainsString('Requested command has not found.', $output->getDisplay());
		$this->assertStringContainsString('try "./sakoo assist help" to get more information', $output->getDisplay());
	}

	#[Test]
	public function it_throws_exception_if_default_command_not_found(): void
	{
		$this->expectException(CommandNotFoundException::class);

		$console = new Application(new Input([]), new Output());
		$console->setDefaultCommand(ZenCommand::class);
	}

	#[Test]
	public function add_commands_registers_multiple_commands_at_once(): void
	{
		$console = new Application(new Input(), new Output());
		$console->addCommands([new VersionCommand(), new ZenCommand()]);

		$this->assertCount(2, $console->getCommands());
		$this->assertArrayHasKey('version', $console->getCommands());
		$this->assertArrayHasKey('zen', $console->getCommands());
	}
}
