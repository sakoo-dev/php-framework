<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Contracts;

/**
 * Describes a container that supports persistence of its binding map to a cache file
 * and restoration from it on subsequent requests.
 *
 * Caching the container avoids repeating reflection-based autowiring on every boot,
 * which measurably reduces cold-start latency in production. Implementations must
 * ensure that the dumped cache is a valid PHP file that can be included directly
 * (returning the binding array) and that the cache is invalidated whenever the
 * service loader configuration changes.
 */
interface ShouldCache
{
	/**
	 * Populates the container's internal binding and singleton maps from a previously
	 * dumped cache file. Should only be called after confirming cacheExists() is true.
	 *
	 * @throws \Throwable
	 */
	public function loadCache(): void;

	/**
	 * Deletes the cache file from disk and returns true on success, or false when no
	 * cache file exists.
	 */
	public function flushCache(): bool;

	/**
	 * Returns true when a valid cache file is present on disk and ready to be loaded.
	 */
	public function cacheExists(): bool;

	/**
	 * Serialises the current binding and singleton maps to a PHP cache file so that
	 * future boots can skip autowiring by calling loadCache() instead.
	 *
	 * @throws \Throwable
	 */
	public function dumpCache(): void;
}
