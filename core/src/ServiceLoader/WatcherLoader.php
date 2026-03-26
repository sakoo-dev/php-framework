<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\ServiceLoader;

use Sakoo\Framework\Core\Commands\Watcher\PhpBundler;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Watcher\Contracts\File;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;
use Sakoo\Framework\Core\Watcher\Contracts\WatcherDriver;
use Sakoo\Framework\Core\Watcher\Inotify\File as InotifyFile;
use Sakoo\Framework\Core\Watcher\Inotify\Inotify;

/**
 * Service loader that registers the filesystem watcher bindings.
 *
 * Wires the three watcher contracts to their Linux inotify-backed implementations:
 *
 * - FileSystemAction → PhpBundler  (action executed on every detected file change)
 * - WatcherDriver    → Inotify     (inotify-based filesystem event driver)
 * - File             → InotifyFile (value object representing a watched file event)
 *
 * All three are registered as transient bindings because each WatchCommand
 * invocation creates its own watcher lifecycle and shares no state across runs.
 */
class WatcherLoader extends ServiceLoader
{
	/**
	 * Registers the watcher interface-to-implementation bindings into $container.
	 */
	public function load(Container $container): void
	{
		$container->bind(FileSystemAction::class, PhpBundler::class);
		$container->bind(WatcherDriver::class, Inotify::class);
		$container->bind(File::class, InotifyFile::class);
	}
}
