<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump;

/**
 * Global entry point for debug-dumping values at runtime.
 *
 * Resolves the active Dumper implementation from the container and delegates to it,
 * keeping dump logic decoupled from output channel specifics. This means the
 * rendering strategy (HTML, CLI, log) can be swapped by rebinding Dumper in the
 * container without touching any call site.
 *
 * Two static methods are provided:
 * - dump()     — renders one or more values and returns normally.
 * - dieDump()  — renders one or more values then terminates the process immediately.
 *
 * These are exposed as global helper functions dump() and dd() in helpers.php for
 * ergonomic use throughout the codebase.
 */
class VarDump
{
	/**
	 * Renders each value in $vars through the active Dumper and then terminates
	 * the process. Equivalent to calling dump() followed by exit.
	 */
	public static function dieDump(mixed ...$vars): never
	{
		static::dump(...$vars);

		exit;
	}

	/**
	 * Resolves the active Dumper from the container and passes each value in $vars
	 * to its dump() method in order.
	 */
	public static function dump(mixed ...$vars): void
	{
		/** @var Dumper $dumper */
		$dumper = resolve(Dumper::class);

		foreach ($vars as $var) {
			$dumper->dump($var);
		}
	}
}
