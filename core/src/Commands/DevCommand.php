<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

class DevCommand extends Command
{
	public static function getName(): string
	{
		return 'dev';
	}

	public static function getDescription(): string
	{
		return 'Useful Information for Developer';
	}

	public function run(Input $input, Output $output): int
	{
		$jit = 'Unknown';

		if ($opcache = opcache_get_status()) {
			// @phpstan-ignore offsetAccess.nonOffsetAccessible
			$jit = $opcache['jit']['enabled'] ? 'Enabled' : 'Disabled';
		}

		$output->block("JIT Enabled: $jit", Output::COLOR_GREEN);

		return Output::SUCCESS;
	}
}
