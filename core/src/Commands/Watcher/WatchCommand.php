<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands\Watcher;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Watcher\Watcher;

/**
 * Console command that starts the filesystem watcher for PHP source files.
 *
 * Registers all PHP files found under the project root with the injected Watcher,
 * using a PhpBundler instance as the change handler, then enters the Watcher's
 * infinite event loop. The command blocks indefinitely — it is intended to be run
 * in a dedicated terminal window or supervised process during development.
 *
 * On each detected file change PhpBundler runs PHP CS Fixer on the modified file
 * and logs the event to the console output.
 */
class WatchCommand extends Command
{
	public function __construct(private readonly Watcher $watcher) {}

	/**
	 * Returns the CLI argument name 'watch' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'watch';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'Run the file Watcher';
	}

	/**
	 * Registers all project PHP files with the watcher, prints a "Watching …"
	 * status line, then enters the blocking event loop. Never returns under
	 * normal operation.
	 */
	public function run(Input $input, Output $output): int
	{
		$this->watcher->watch(Path::getProjectPHPFiles(), new PhpBundler($input, $output));
		$output->block('Watching ...', Output::COLOR_CYAN);
		$this->watcher->run();

		return Output::SUCCESS;
	}
}
