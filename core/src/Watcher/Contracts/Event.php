<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Contracts;

use Sakoo\Framework\Core\Watcher\EventTypes;

/**
 * Represents a single filesystem event emitted by a WatcherDriver.
 *
 * An Event captures everything needed to identify what happened and to whom:
 * the affected file (via getFile()), the watch descriptor ID (via getHandlerId()),
 * the event category (via getType()), and the resolved file name (via getName()).
 *
 * Watcher dispatches events to the correct FileSystemAction method by matching
 * on getType(). For DELETE events, getHandlerId() is passed to WatcherDriver::blind()
 * to remove the watch descriptor so no further events are received for that path.
 */
interface Event
{
	/**
	 * Returns the File value object representing the watched file that triggered
	 * this event, including its associated callback and locker.
	 */
	public function getFile(): File;

	/**
	 * Returns the integer watch descriptor (inotify wd) that identifies the watch
	 * registration for the affected file. Used to remove the watch on DELETE events.
	 */
	public function getHandlerId(): int;

	/**
	 * Returns the EventTypes case describing the kind of filesystem change that
	 * occurred (MODIFY, MOVE, or DELETE).
	 */
	public function getType(): EventTypes;

	/**
	 * Returns the resolved path or name of the file associated with this event.
	 */
	public function getName(): string;
}
