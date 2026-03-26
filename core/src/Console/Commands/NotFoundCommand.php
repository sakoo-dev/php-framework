<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

/**
 * Built-in fallback command executed when the requested command name is not registered.
 *
 * Application resolves this command whenever the first positional argument does not
 * match any entry in the command registry and is not a built-in trigger (help,
 * version). It prints an error message in red and a usage hint in green, then
 * returns Output::ERROR to signal a non-zero exit code to the shell.
 */
class NotFoundCommand extends Command
{
	/**
	 * Returns the internal name 'not-found'. This command is never invoked by the
	 * user directly — it is selected automatically by Application as the fallback.
	 */
	public static function getName(): string
	{
		return 'not-found';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'This command will be called when a user requested command is not found';
	}

	/**
	 * Prints a "command not found" error and a usage hint, then returns Output::ERROR.
	 */
	public function run(Input $input, Output $output): int
	{
		$output->block('Requested command has not found.', Output::COLOR_RED);
		$output->block('try "./sakoo assist help" to get more information', Output::COLOR_GREEN);

		return Output::ERROR;
	}
}
