<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Locker;

/**
 * Simple in-memory boolean lock for protecting critical sections.
 *
 * Provides a lightweight mutex-like flag that a single process can set and query
 * to guard a critical section from re-entrant or concurrent access within the
 * same request lifecycle. Because the state is held in an instance property it is
 * not shared across processes or requests — this is not a distributed lock.
 *
 * Typical usage is to lock before entering a critical operation and unlock in a
 * finally block to ensure the flag is always cleared even if an exception is thrown.
 */
class Locker
{
	private bool $locked = false;

	/**
	 * Acquires the lock, marking the critical section as entered.
	 */
	public function lock(): void
	{
		$this->locked = true;
	}

	/**
	 * Releases the lock, allowing the critical section to be entered again.
	 */
	public function unlock(): void
	{
		$this->locked = false;
	}

	/**
	 * Returns true when the lock is currently held, false otherwise.
	 */
	public function isLocked(): bool
	{
		return $this->locked;
	}
}
