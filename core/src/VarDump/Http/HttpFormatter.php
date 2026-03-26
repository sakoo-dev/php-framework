<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump\Http;

use Sakoo\Framework\Core\VarDump\Formatter;

/**
 * HTTP formatter for debug-dumping PHP values into an HTML response.
 *
 * Implements the Formatter contract for the HTTP mode. Rendering is intentionally
 * deferred to a future implementation — the current body is a stub that accepts
 * any value and produces no output, acting as a safe no-op until an HTML rendering
 * strategy is wired in.
 */
readonly class HttpFormatter implements Formatter
{
	public function __construct() {}

	/**
	 * Renders $value into the HTTP response output channel.
	 * Currently a no-op stub pending a concrete HTML rendering implementation.
	 */
	public function format(mixed $value): void {}
}
