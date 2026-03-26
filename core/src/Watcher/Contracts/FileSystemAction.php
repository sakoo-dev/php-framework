<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Watcher\Contracts;

/**
 * Callback contract invoked by Watcher when a watched file changes.
 *
 * Each method corresponds to one EventTypes case and receives the full Event
 * value object so implementations can inspect the file path, handle ID, and event
 * metadata before taking action (e.g. re-bundling PHP files, clearing caches,
 * reloading configuration).
 *
 * Implementations should be registered in the container via WatcherLoader and
 * are injected into the Watcher by WatchCommand at startup.
 */
interface FileSystemAction
{
	/**
	 * Called when a watched file's content has been written or truncated.
	 */
	public function fileModified(Event $event): void;

	/**
	 * Called when a watched file has been renamed or moved to a different path.
	 */
	public function fileMoved(Event $event): void;

	/**
	 * Called when a watched file has been deleted from the filesystem.
	 */
	public function fileDeleted(Event $event): void;
}
