<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump;

/**
 * Contract for value dumping implementations.
 *
 * A Dumper receives a single value of any type and renders a human-readable
 * representation to an output channel (HTML, CLI, log, etc.) determined by the
 * concrete implementation. The Dumper interface is kept deliberately narrow — one
 * method, one value — so implementations remain focused and swappable via the
 * container without changing call sites.
 */
interface Dumper
{
	/**
	 * Renders a human-readable representation of $value to the implementation's
	 * configured output channel.
	 */
	public function dump(mixed $value): void;
}
