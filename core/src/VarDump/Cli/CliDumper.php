<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump\Cli;

use Sakoo\Framework\Core\VarDump\Dumper;
use Sakoo\Framework\Core\VarDump\Formatter;

/**
 * CLI implementation of the Dumper contract.
 *
 * Delegates all rendering to the injected Formatter (typically CliFormatter),
 * keeping the dumper itself free of any output or formatting logic. Registered
 * in the container for console/test mode so that dump() and dd() produce
 * ANSI-coloured output on the terminal instead of HTML markup.
 */
readonly class CliDumper implements Dumper
{
	public function __construct(private Formatter $formatter) {}

	/**
	 * Passes $value to the formatter, which renders it to the terminal output channel.
	 */
	public function dump(mixed $value): void
	{
		$this->formatter->format($value);
	}
}
