<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Contracts;

use Sakoo\Framework\Core\Locker\Locker;

/**
 * Represents a file that is registered for filesystem watching.
 *
 * Carries the inotify watch descriptor ID, the absolute path being watched,
 * the FileSystemAction callback to invoke when the file changes, and a Locker
 * instance used by WatcherActions to prevent re-entrant handling of rapid
 * successive MODIFY events on the same file.
 */
interface File
{
	/**
	 * Returns the integer watch descriptor assigned by the WatcherDriver when this
	 * file was registered for watching.
	 */
	public function getId(): int;

	/**
	 * Returns the FileSystemAction callback associated with this file. Invoked by
	 * Watcher when a filesystem event is received for this path.
	 */
	public function getCallback(): FileSystemAction;

	/**
	 * Returns the absolute filesystem path of the watched file.
	 */
	public function getPath(): string;

	/**
	 * Returns the Locker instance used to debounce rapid consecutive MODIFY events
	 * on this file, preventing duplicate action invocations.
	 */
	public function getLocker(): Locker;
}
