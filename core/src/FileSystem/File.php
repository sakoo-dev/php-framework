<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

/**
 * Static factory for opening Storage instances.
 *
 * Decouples call sites from concrete Storage implementations by accepting a Disk
 * enum case that carries the fully-qualified class name of the desired driver.
 * open() instantiates the appropriate driver with the given path and returns it
 * as a Storage contract, so consumers never depend on a concrete class directly.
 *
 * The private constructor prevents instantiation — this class is intentionally a
 * pure static factory with no instance state.
 */
class File
{
	/**
	 * Opens a file at $path using the storage driver identified by $storage.
	 *
	 * The Disk enum value is used as the class name to instantiate, allowing new
	 * storage backends to be added by defining a new Disk case without modifying
	 * this factory.
	 */
	public static function open(Disk $storage, string $path): Storage
	{
		return new $storage->value($path);
	}

	private function __construct() {}
}
