<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Inotify;

use Sakoo\Framework\Core\Watcher\Contracts\Event as EventInterface;
use Sakoo\Framework\Core\Watcher\Contracts\File;
use Sakoo\Framework\Core\Watcher\EventTypes;

/**
 * Inotify-backed value object representing a single filesystem event.
 *
 * Wraps the raw associative array returned by inotify_read() and enriches it
 * with the File value object that was registered for the triggering watch
 * descriptor. The inotify event mask is decoded into an EventTypes case by
 * testing against the IN_MODIFY, IN_MOVE_SELF, and IN_DELETE_SELF bitmask
 * constants. When none of the expected bits are set, MODIFY is returned as
 * a safe default.
 *
 * The cookie field exposed by getGroupId() is the inotify rename cookie that
 * links paired IN_MOVED_FROM / IN_MOVED_TO events for atomic renames; it is
 * zero for all other event types.
 */
class Event implements EventInterface
{
	private int $wd;
	private int $mask;
	private int $cookie;
	private string $name;

	/**
	 * Constructs the Event from the raw inotify event array and the pre-resolved
	 * File value object for the affected watch descriptor.
	 *
	 * @param int[] $event raw inotify event array with keys 'wd', 'mask', 'cookie', 'name'
	 */
	public function __construct(private readonly File $file, array $event)
	{
		$this->wd = $event['wd'];
		$this->mask = $event['mask'];
		$this->cookie = $event['cookie'];
		$this->name = $file->getPath();
	}

	/**
	 * Returns the File value object for the watched path that triggered this event.
	 */
	public function getFile(): File
	{
		return $this->file;
	}

	/**
	 * Returns the inotify watch descriptor integer identifying the registered watch.
	 * Passed to WatcherDriver::blind() when a DELETE event is processed.
	 */
	public function getHandlerId(): int
	{
		return $this->wd;
	}

	/**
	 * Decodes the inotify bitmask into an EventTypes case.
	 *
	 * Tests IN_MODIFY first, then IN_MOVE_SELF, then IN_DELETE_SELF. Falls back
	 * to EventTypes::MODIFY when no recognised bit is set.
	 */
	public function getType(): EventTypes
	{
		if ($this->mask & IN_MODIFY) {
			return EventTypes::MODIFY;
		}

		if ($this->mask & IN_MOVE_SELF) {
			return EventTypes::MOVE;
		}

		if ($this->mask & IN_DELETE_SELF) {
			return EventTypes::DELETE;
		}

		return EventTypes::MODIFY;
	}

	/**
	 * Returns the inotify rename cookie that correlates paired MOVED_FROM /
	 * MOVED_TO events for atomic renames. Zero for all other event types.
	 */
	public function getGroupId(): int
	{
		return $this->cookie;
	}

	/**
	 * Returns the absolute path of the file that triggered this event.
	 */
	public function getName(): string
	{
		return $this->name;
	}
}
