<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Constants;

/**
 * Built-in command that prints the framework name and version string.
 *
 * Invoked automatically by Application when the user passes --version / -v or
 * types 'version' as the first argument. Reads the framework identity from the
 * Constants class so the displayed string always reflects the current release
 * without being duplicated in multiple places.
 */
class VersionCommand extends Command
{
	/**
	 * Returns the CLI argument name 'version' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'version';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'This command shows software version information';
	}

	/**
	 * Prints the framework name and version in green, then returns Output::SUCCESS.
	 */
	public function run(Input $input, Output $output): int
	{
		$output->block(Constants::FRAMEWORK_NAME . ' - Version: ' . Constants::FRAMEWORK_VERSION, Output::COLOR_GREEN);

		return Output::SUCCESS;
	}
}
