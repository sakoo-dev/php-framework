<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

/**
 * Console command that displays runtime developer diagnostics.
 *
 * Currently reports OPcache JIT status (Enabled / Disabled / Unknown) by
 * inspecting the opcache_get_status() result. Additional diagnostics can be
 * appended here as the framework evolves without changing the command name or
 * registration.
 *
 * Intended as a quick sanity-check during local development and CI to confirm
 * that performance-sensitive runtime extensions are active.
 */
class DevCommand extends Command
{
	/**
	 * Returns the CLI argument name 'dev' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'dev';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'Useful Information for Developer';
	}

	/**
	 * Queries OPcache for JIT status and prints the result in green.
	 * Reports 'Unknown' when OPcache is not loaded or its status is unavailable.
	 */
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
