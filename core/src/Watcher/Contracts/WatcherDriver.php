<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Contracts;

use Sakoo\Framework\Core\Set\IterableInterface;

/**
 * Port for the underlying kernel-level filesystem event mechanism.
 *
 * Abstracts the OS-specific watcher API (inotify on Linux, FSEvents on macOS,
 * ReadDirectoryChangesW on Windows) behind a uniform interface so the Watcher
 * class and the rest of the framework are not coupled to any one implementation.
 *
 * Lifecycle:
 * 1. watch() — registers a path and associates a FileSystemAction callback with it.
 * 2. wait()  — blocks until at least one event is available and returns them as a Set.
 * 3. blind() — removes a watch descriptor after a DELETE event so no further events
 *              are delivered for that path.
 */
interface WatcherDriver
{
	/**
	 * Registers $file for watching and associates $callback with it. Subsequent
	 * events for $file will carry a reference to the registered FileSystemAction.
	 */
	public function watch(string $file, FileSystemAction $callback): void;

	/**
	 * Blocks until one or more filesystem events are available from the kernel,
	 * then returns them as a Set of Event objects. Returns an empty Set when no
	 * events are pending.
	 *
	 * @return IterableInterface<Event>
	 */
	public function wait(): IterableInterface;

	/**
	 * Removes the watch descriptor identified by $id, stopping all future event
	 * delivery for the corresponding path. Returns true on success.
	 */
	public function blind(int $id): bool;
}
