<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Constants;

/**
 * Console command that displays the framework identity banner.
 *
 * Prints a decorative ASCII art block in cyan followed by the framework name,
 * current version in green, and a copyright line crediting the maintainer.
 * Modelled after the "Zen of Python" easter egg — a lighthearted way to
 * confirm that the framework is correctly installed and the CLI is functional.
 */
class ZenCommand extends Command
{
	/**
	 * Returns the CLI argument name 'zen' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'zen';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'Display Zen of the ' . Constants::FRAMEWORK_NAME;
	}

	/**
	 * Renders the decorative banner, framework name, version, and copyright year.
	 */
	public function run(Input $input, Output $output): int
	{
		$output->block([
			"\t\t=======================",
			"\t\t=========",
			' =======================',
		], Output::COLOR_CYAN);

		$output->block(Constants::FRAMEWORK_NAME . ' (Version: ' . Constants::FRAMEWORK_VERSION . ')', Output::COLOR_GREEN);
		$output->block('Copyright ' . date('Y') . ' by ' . Constants::MAINTAINER);

		return Output::SUCCESS;
	}
}
