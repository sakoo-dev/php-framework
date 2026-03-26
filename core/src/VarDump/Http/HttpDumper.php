<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump\Http;

use Sakoo\Framework\Core\VarDump\Dumper;
use Sakoo\Framework\Core\VarDump\Formatter;

/**
 * HTTP implementation of the Dumper contract.
 *
 * Delegates all rendering to the injected Formatter (typically HttpFormatter),
 * keeping the dumper itself free of any output or formatting logic. Registered
 * in the container for HTTP mode so that dump() and dd() write debug output
 * directly into the HTTP response body as HTML.
 */
readonly class HttpDumper implements Dumper
{
	public function __construct(private Formatter $formatter) {}

	/**
	 * Passes $value to the formatter, which renders it into the HTTP response output channel.
	 */
	public function dump(mixed $value): void
	{
		$this->formatter->format($value);
	}
}
