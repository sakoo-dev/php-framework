<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem;

/**
 * Named constants and factory methods for Unix-style octal file permission strings.
 *
 * The eight integer constants map directly to the standard Unix permission bits:
 * read (4), write (2), execute (1), and their combinations. The static factory
 * methods compose these bits into a four-character octal string of the form
 * "0UGO" (leading zero, then user/group/other digits) accepted by chmod().
 *
 * The "all*" helpers set the same permission for user, group, and others in one
 * call. The "get*" group methods return arrays of all octal strings that satisfy
 * a specific predicate (e.g. all readable combinations) — useful for asserting
 * permissions in tests or validating filesystem state.
 *
 * getFileDefault() and getDirectoryDefault() return the conventional permission
 * strings used when creating new files and directories respectively.
 */
class Permission
{
	/** No permissions. */
	final public const int NOTHING = 0;

	/** Execute-only permission. */
	final public const int EXECUTE = 1;

	/** Write-only permission. */
	final public const int WRITE = 2;

	/** Write and execute permissions. */
	final public const int EXECUTE_WRITE = 3;

	/** Read-only permission. */
	final public const int READ = 4;

	/** Read and execute permissions. */
	final public const int EXECUTE_READ = 5;

	/** Read and write permissions. */
	final public const int WRITE_READ = 6;

	/** Full permissions: read, write, and execute. */
	final public const int EXECUTE_WRITE_READ = 7;

	/**
	 * Returns the octal string "0000" — no permissions for user, group, or others.
	 */
	public static function allNothing(): string
	{
		return static::make(static::NOTHING, static::NOTHING, static::NOTHING);
	}

	/**
	 * Returns the octal string "0111" — execute-only for user, group, and others.
	 */
	public static function allExecute(): string
	{
		return static::make(static::EXECUTE, static::EXECUTE, static::EXECUTE);
	}

	/**
	 * Returns the octal string "0222" — write-only for user, group, and others.
	 */
	public static function allWrite(): string
	{
		return static::make(static::WRITE, static::WRITE, static::WRITE);
	}

	/**
	 * Returns the octal string "0333" — write and execute for user, group, and others.
	 */
	public static function allExecuteWrite(): string
	{
		return static::make(static::EXECUTE_WRITE, static::EXECUTE_WRITE, static::EXECUTE_WRITE);
	}

	/**
	 * Returns the octal string "0444" — read-only for user, group, and others.
	 */
	public static function allRead(): string
	{
		return static::make(static::READ, static::READ, static::READ);
	}

	/**
	 * Returns the octal string "0555" — read and execute for user, group, and others.
	 */
	public static function allExecuteRead(): string
	{
		return static::make(static::EXECUTE_READ, static::EXECUTE_READ, static::EXECUTE_READ);
	}

	/**
	 * Returns the octal string "0666" — read and write for user, group, and others.
	 */
	public static function allWriteRead(): string
	{
		return static::make(static::WRITE_READ, static::WRITE_READ, static::WRITE_READ);
	}

	/**
	 * Returns the octal string "0777" — full permissions for user, group, and others.
	 */
	public static function allExecuteWriteRead(): string
	{
		return static::make(static::EXECUTE_WRITE_READ, static::EXECUTE_WRITE_READ, static::EXECUTE_WRITE_READ);
	}

	/**
	 * Returns the four permission strings that include the execute bit for all principals.
	 *
	 * @return string[]
	 */
	public static function getExecutables(): array
	{
		return [static::allExecuteWriteRead(), static::allExecuteRead(), static::allExecuteWrite(), static::allExecute()];
	}

	/**
	 * Returns the four permission strings that do not include the execute bit.
	 *
	 * @return string[]
	 */
	public static function getNotExecutables(): array
	{
		return [static::allWriteRead(), static::allRead(), static::allWrite(), static::allNothing()];
	}

	/**
	 * Returns the four permission strings that include the write bit for all principals.
	 *
	 * @return string[]
	 */
	public static function getWritables(): array
	{
		return [static::allExecuteWriteRead(), static::allWriteRead(), static::allExecuteWrite(), static::allWrite()];
	}

	/**
	 * Returns the four permission strings that do not include the write bit.
	 *
	 * @return string[]
	 */
	public static function getNotWritables(): array
	{
		return [static::allExecuteRead(), static::allRead(), static::allExecute(), static::allNothing()];
	}

	/**
	 * Returns the four permission strings that include the read bit for all principals.
	 *
	 * @return string[]
	 */
	public static function getReadables(): array
	{
		return [static::allExecuteWriteRead(), static::allWriteRead(), static::allExecuteRead(), static::allRead()];
	}

	/**
	 * Returns the four permission strings that do not include the read bit.
	 *
	 * @return string[]
	 */
	public static function getNotReadables(): array
	{
		return [static::allExecuteWrite(), static::allWrite(), static::allExecute(), static::allNothing()];
	}

	/**
	 * Composes $user, $group, and $others permission bits into a four-character octal
	 * string prefixed with a leading zero (e.g. "0755"), ready for use with chmod().
	 */
	public static function make(int $user, int $group, int $others): string
	{
		return '0' . $user . $group . $others;
	}

	/**
	 * Returns the default permission string for newly created files: "0644"
	 * (owner read/write, group and others read-only).
	 */
	public static function getFileDefault(): string
	{
		return static::make(static::WRITE_READ, static::READ, static::READ);
	}

	/**
	 * Returns the default permission string for newly created directories: "0755"
	 * (owner full access, group and others read/execute).
	 */
	public static function getDirectoryDefault(): string
	{
		return static::make(static::EXECUTE_WRITE_READ, static::EXECUTE_READ, static::EXECUTE_READ);
	}
}
