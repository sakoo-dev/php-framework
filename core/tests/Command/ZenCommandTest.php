<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\ZenCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Constants;

final class ZenCommandTest extends AbstractCommandBase
{
	private Command $command;

	protected function setUp(): void
	{
		parent::setUp();
		$this->command = new ZenCommand();
	}

	protected function getCommand(): Command
	{
		return $this->command;
	}

	#[Test]
	public function command_works_properly(): void
	{
		$input = new Input(['zen']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$status = $console->run();
		$result = $output->getDisplay();

		$this->assertEquals(Output::SUCCESS, $status);
		$this->assertStringContainsString(Constants::FRAMEWORK_NAME . ' (Version: ' . Constants::FRAMEWORK_VERSION . ')', $result);
		$this->assertStringContainsString('Copyright ' . date('Y') . ' by ' . Constants::MAINTAINER, $result);
	}
}
