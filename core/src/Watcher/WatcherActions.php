<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher;

use Sakoo\Framework\Core\Watcher\Contracts\Event;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;

/**
 * Convenient base class for FileSystemAction implementations.
 *
 * Provides default (no-op) implementations of fileMoved() and fileDeleted() so
 * subclasses only need to override the event types they care about. The
 * fileModified() override adds debounce protection via the file's Locker: if the
 * locker is already held when a MODIFY event arrives (i.e. a previous handling of
 * the same file is still in progress), the event is silently dropped. Otherwise
 * the locker is acquired before the subclass logic runs.
 *
 * Subclasses should call parent::fileModified() at the start of their override, or
 * rely on Watcher-level sequencing to ensure the locker is managed correctly.
 */
abstract class WatcherActions implements FileSystemAction
{
	/**
	 * Guards against re-entrant handling of MODIFY events on the same file.
	 *
	 * Checks the file's Locker; returns immediately (drops the event) when the lock
	 * is already held, otherwise acquires it so the subclass can proceed safely.
	 */
	public function fileModified(Event $event): void
	{
		$locker = $event->getFile()->getLocker();

		if ($locker->isLocked()) {
			return;
		}

		$locker->lock();
	}

	/**
	 * Called when a watched file is moved or renamed. No-op by default.
	 */
	public function fileMoved(Event $event): void {}

	/**
	 * Called when a watched file is deleted. No-op by default.
	 */
	public function fileDeleted(Event $event): void {}
}
