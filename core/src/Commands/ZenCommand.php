<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Constants;

class ZenCommand extends Command
{
	public static function getName(): string
	{
		return 'zen';
	}

	public static function getDescription(): string
	{
		return 'Display Zen of the ' . Constants::FRAMEWORK_NAME;
	}

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
