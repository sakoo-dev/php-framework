<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher;

use Sakoo\Framework\Core\Watcher\Contracts\Event;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;
use Sakoo\Framework\Core\Watcher\Contracts\WatcherDriver;

/**
 * High-level filesystem watcher that coordinates a driver with a callback action.
 *
 * Accepts a set of files to monitor and a FileSystemAction callback, registers each
 * file with the underlying WatcherDriver, then enters an event loop that waits for
 * kernel-level filesystem notifications and dispatches them to the appropriate
 * callback method based on the event type.
 *
 * Three event types are handled:
 * - MODIFY — file content was changed; calls FileSystemAction::fileModified().
 * - MOVE   — file was renamed or moved; calls FileSystemAction::fileMoved().
 * - DELETE — file was removed; calls FileSystemAction::fileDeleted() and removes
 *             the watch descriptor from the driver via blind().
 *
 * run() blocks indefinitely in a while(true) loop. Use check() directly in tests
 * or custom loop implementations to process a single batch of events at a time.
 */
class Watcher
{
	public function __construct(private readonly WatcherDriver $driver) {}

	/**
	 * Registers each file in $files with the driver, associating it with $callback
	 * so the correct action is invoked when the file changes. Returns the same
	 * Watcher instance for fluent chaining.
	 *
	 * @param \SplFileObject[] $files
	 */
	public function watch(array $files, FileSystemAction $callback): self
	{
		foreach ($files as $file) {
			$this->driver->watch($file->getRealPath(), $callback);
		}

		return $this;
	}

	/**
	 * Enters an infinite event loop, repeatedly calling check() to wait for and
	 * dispatch filesystem events. This method never returns under normal operation.
	 */
	public function run(): void
	{
		// @phpstan-ignore while.alwaysTrue
		while (true) {
			$this->check();
		}
	}

	/**
	 * Waits for the next batch of filesystem events from the driver and dispatches
	 * each one to the appropriate FileSystemAction method via eventCall().
	 */
	public function check(): void
	{
		$eventSet = $this->driver->wait();
		$eventSet->each(fn (Event $event) => $this->eventCall($event));
	}

	/**
	 * Dispatches a single filesystem event to the callback associated with the
	 * affected file. For DELETE events, the watch descriptor is also removed from
	 * the driver so it no longer receives events for the deleted path.
	 */
	private function eventCall(Event $event): void
	{
		$callback = $event->getFile()->getCallback();

		match ($event->getType()) {
			EventTypes::MODIFY => $callback->fileModified($event),
			EventTypes::MOVE => $callback->fileMoved($event),
			// @phpstan-ignore booleanAnd.leftAlwaysFalse, method.void
			EventTypes::DELETE => $callback->fileDeleted($event) && $this->driver->blind($event->getHandlerId()),
		};
	}
}
