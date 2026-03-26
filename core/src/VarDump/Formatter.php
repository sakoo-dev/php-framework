<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump;

/**
 * Contract for rendering a single value to a specific output channel.
 *
 * Implementations produce a human-readable representation of $value and write
 * it directly to their target output (ANSI terminal, HTML response, log file,
 * etc.). The channel and visual style are encapsulated in the concrete class,
 * keeping Dumper implementations free of formatting concerns.
 */
interface Formatter
{
	/**
	 * Renders a human-readable representation of $value to the formatter's
	 * configured output channel.
	 */
	public function format(mixed $value): void;
}
