<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

class ExampleCommand extends Command
{
	public static function getName(): string
	{
		return 'example';
	}

	public static function getDescription(): string
	{
		return 'An example command';
	}

	public function run(Input $input, Output $output): int
	{
		$output->block('It works properly', Output::COLOR_GREEN);

		return Output::SUCCESS;
	}
}
