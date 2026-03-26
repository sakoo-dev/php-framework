<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

/**
 * Filesystem abstraction port for a single file or directory node.
 *
 * Implementations (adapters) back this interface against a concrete storage medium
 * such as the local disk, an in-memory store, or a remote object storage provider.
 * All framework code that touches the filesystem depends only on this interface so
 * the underlying storage driver can be swapped or faked in tests without changing
 * any application logic.
 *
 * Each Storage instance represents one path. Mutation methods return a bool
 * indicating success or failure rather than throwing, keeping call sites simple;
 * callers that require strict error handling should assert the return value.
 */
interface Storage
{
	/**
	 * Creates the file (or a directory when $asDirectory is true) at the configured
	 * path. Returns true on success, false when the node already exists or the
	 * operation fails.
	 */
	public function create(bool $asDirectory = false): bool;

	/**
	 * Creates the directory at the configured path, including all missing parent
	 * directories when $recursive is true. Returns true on success.
	 */
	public function mkdir(bool $recursive = true): bool;

	/**
	 * Returns true when the file or directory exists at the configured path.
	 */
	public function exists(): bool;

	/**
	 * Deletes the file or directory at the configured path. Returns true on success.
	 */
	public function remove(): bool;

	/**
	 * Returns true when the configured path points to a directory rather than a file.
	 */
	public function isDir(): bool;

	/**
	 * Moves (renames) the node from the configured path to $to. Returns true on success.
	 */
	public function move(string $to): bool;

	/**
	 * Copies the file from the configured path to $to. Returns true on success.
	 */
	public function copy(string $to): bool;

	/**
	 * Lists the names of all immediate children when the configured path is a directory.
	 *
	 * @return string[]
	 */
	public function files(): array;

	/**
	 * Returns the absolute path to the parent directory of the configured path.
	 */
	public function parentDir(): string;

	/**
	 * Renames the node to $to within the same directory. Returns true on success.
	 */
	public function rename(string $to): bool;

	/**
	 * Overwrites the file content at the configured path with $data. Creates the
	 * file and any missing parent directories if they do not yet exist. Returns true
	 * on success.
	 */
	public function write(string $data): bool;

	/**
	 * Appends $data to the end of the file at the configured path, creating the file
	 * and any missing parent directories if necessary. Returns true on success.
	 */
	public function append(string $data): bool;

	/**
	 * Reads the file at the configured path and returns its contents as an ordered
	 * array of lines (without the trailing newline character). Returns false on failure.
	 *
	 * @return false|string[]
	 */
	public function readLines(): array|false;

	/**
	 * Sets the filesystem permission bits on the node. Accepts both octal integers
	 * (e.g. 0755) and symbolic string representations. Returns true on success.
	 */
	public function setPermission(int|string $permission): bool;

	/**
	 * Returns the current filesystem permission of the node. The format of the
	 * returned value is driver-specific.
	 */
	public function getPermission(): mixed;

	/**
	 * Returns the absolute path this Storage instance was opened with.
	 */
	public function getPath(): string;
}
