<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Inotify;

use Sakoo\Framework\Core\Locker\Locker;
use Sakoo\Framework\Core\Set\IterableInterface;
use Sakoo\Framework\Core\Watcher\Contracts\Event as EventInterface;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;
use Sakoo\Framework\Core\Watcher\Contracts\WatcherDriver;

/**
 * Linux inotify-backed implementation of WatcherDriver.
 *
 * Uses the inotify PHP extension to subscribe to kernel-level filesystem
 * notifications. Three inotify event masks are combined for each watched path:
 * IN_MODIFY (content change), IN_MOVE_SELF (the file itself was renamed/moved),
 * and IN_DELETE_SELF (the file itself was deleted).
 *
 * Registered watch descriptors and their associated File value objects are kept
 * in an internal Set keyed by the string representation of the watch descriptor
 * integer, so Event objects returned by wait() can be enriched with the correct
 * File reference.
 *
 * Each file is given its own Locker instance (resolved from the container) so
 * WatcherActions can debounce rapid MODIFY events per file independently.
 */
class Inotify implements WatcherDriver
{
	/** @var resource */
	private $inotify;

	/**
	 * @var IterableInterface<File|string>
	 */
	private IterableInterface $handlerSet;

	/**
	 * Initialises the inotify instance and the internal watch-descriptor registry.
	 */
	public function __construct()
	{
		$this->handlerSet = set();
		$this->inotify = inotify_init();
	}

	/**
	 * Adds a watch for $file with IN_MODIFY | IN_MOVE_SELF | IN_DELETE_SELF masks,
	 * creates a File value object with the returned watch descriptor and a fresh
	 * Locker, and stores both in the handler registry.
	 */
	public function watch(string $file, FileSystemAction $callback): void
	{
		$masks = IN_MODIFY | IN_MOVE_SELF | IN_DELETE_SELF;
		$wd = inotify_add_watch($this->inotify, $file, $masks);
		/** @var Locker $locker */
		$locker = makeInstance(Locker::class);
		// @phpstan-ignore argument.type
		$this->handlerSet->add((string) $wd, new File($wd, $file, $callback, $locker));
	}

	/**
	 * Blocks on inotify_read() until at least one event is delivered, then
	 * constructs an Event value object for each raw inotify event and returns them
	 * as a Set. Returns an empty Set when inotify_read() yields no events.
	 *
	 * @return IterableInterface<EventInterface>
	 */
	public function wait(): IterableInterface
	{
		$eventSet = set();
		$events = inotify_read($this->inotify);

		if (!$events) {
			return $eventSet;
		}

		foreach ($events as $event) {
			/** @var File $handler */
			$handler = $this->handlerSet->get((string) $event['wd']);
			// @phpstan-ignore argument.type
			$eventSet->add(new Event($handler, $event));
		}

		return $eventSet;
	}

	/**
	 * Removes the watch descriptor $id from inotify so no further events are
	 * delivered for the associated path. Returns true on success.
	 */
	public function blind(int $id): bool
	{
		return inotify_rm_watch($this->inotify, $id);
	}
}
