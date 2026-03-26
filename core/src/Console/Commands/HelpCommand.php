<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

/**
 * Built-in command that lists all registered commands and their descriptions.
 *
 * Invoked automatically by Application when the user passes --help / -h with no
 * specific command, types 'help' as the first argument, or provides no argument
 * when no default command is configured. Iterates the Application's command
 * registry and prints each command's name (in bold green) and description (in
 * white) as a formatted block.
 */
class HelpCommand extends Command
{
	/**
	 * Returns the CLI argument name 'help' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'help';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'this command helps the user to interact with the current application';
	}

	/**
	 * Prints all registered commands with their names and descriptions, then
	 * returns Output::SUCCESS.
	 */
	public function run(Input $input, Output $output): int
	{
		$output->block('Assistant Help', Output::COLOR_CYAN);

		$commands = $this->getApplication()->getCommands();

		$output->block('Available commands:');

		/** @var Command $command */
		foreach ($commands as $command) {
			// @phpstan-ignore binaryOp.invalid
			$output->text("\t - " . $command->getName() . ': ', Output::COLOR_GREEN, style: Output::STYLE_BOLD);
			$output->block($command->getDescription(), Output::COLOR_WHITE);
		}

		return Output::SUCCESS;
	}
}
