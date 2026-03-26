<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Inotify;

use Sakoo\Framework\Core\Locker\Locker;
use Sakoo\Framework\Core\Watcher\Contracts\File as FileInterface;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;

/**
 * Inotify-backed value object representing a file registered for watching.
 *
 * Holds the inotify watch descriptor ID assigned by inotify_add_watch(), the
 * absolute path being monitored, the FileSystemAction callback to invoke on
 * events, and a per-file Locker used by WatcherActions to debounce rapid
 * consecutive MODIFY events so the action is not triggered multiple times for
 * a single logical file save.
 *
 * Instances are created by Inotify::watch() and stored in the handler registry
 * keyed by watch descriptor so they can be retrieved when events arrive.
 */
class File implements FileInterface
{
	public function __construct(
		protected int $id,
		protected string $path,
		protected FileSystemAction $callback,
		protected Locker $locker,
	) {}

	/**
	 * Returns the inotify watch descriptor integer assigned when this file was
	 * registered with inotify_add_watch().
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * Returns the FileSystemAction callback to invoke when a filesystem event is
	 * received for this file.
	 */
	public function getCallback(): FileSystemAction
	{
		return $this->callback;
	}

	/**
	 * Returns the absolute filesystem path this instance was registered to watch.
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Returns the Locker instance used to prevent re-entrant handling of
	 * consecutive MODIFY events on this specific file.
	 */
	public function getLocker(): Locker
	{
		return $this->locker;
	}
}
