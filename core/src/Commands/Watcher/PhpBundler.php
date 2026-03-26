<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands\Watcher;

use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Watcher\Contracts\Event;
use Sakoo\Framework\Core\Watcher\WatcherActions;

/**
 * FileSystemAction implementation that auto-formats PHP files on change.
 *
 * Extends WatcherActions so it inherits the per-file Locker debounce guard in
 * fileModified(). When a PHP file is modified:
 *
 * 1. The parent debounce check is applied — the event is dropped if the locker
 *    is already held (i.e. a previous format run for the same file is still in progress).
 * 2. The changed file path is logged to the console output with a timestamp.
 * 3. PHP CS Fixer is run silently on the changed file via exec().
 * 4. The locker is released so future MODIFY events for the same file are processed.
 * 5. A "Watching …" status line is re-printed to confirm the watcher is still active.
 *
 * fileMoved() and fileDeleted() are inherited as no-ops from WatcherActions.
 */
class PhpBundler extends WatcherActions
{
	public function __construct(
		// @phpstan-ignore property.onlyWritten
		private readonly Input $input,
		private readonly Output $output,
	) {}

	/**
	 * Debounces, logs, lints, unlocks, and re-prints the watcher status for every
	 * detected MODIFY event on a watched PHP file.
	 */
	public function fileModified(Event $event): void
	{
		parent::fileModified($event);

		$path = $event->getFile()->getPath();
		$this->output->block("$path changed at " . date('H:i:s'), Output::COLOR_GREEN);

		$this->makeLint($path);

		$event->getFile()->getLocker()->unlock();
		$this->output->block('Watching ...', Output::COLOR_CYAN);
	}

	/**
	 * Runs PHP CS Fixer on $path in quiet mode, applying the project's code style
	 * configuration automatically whenever a file is saved.
	 */
	private function makeLint(string $path): void
	{
		$vendor = Path::getVendorDir();
		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		exec("php $vendor/bin/php-cs-fixer fix $path --quiet");
	}
}
