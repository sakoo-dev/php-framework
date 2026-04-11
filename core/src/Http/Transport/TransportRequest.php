<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Transport;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Converts a raw transport-level request into a PSR-7 ServerRequestInterface.
 *
 * Each transport (Swoole, FPM) provides its own implementation that knows how
 * to extract method, URI, headers, body, cookies, and uploaded files from the
 * underlying runtime and assemble them into an immutable PSR-7 server request.
 *
 * Application code depends on this interface — never on a specific transport.
 */
interface TransportRequest
{
	/**
	 * Creates a PSR-7 ServerRequest from the current transport-level request data.
	 */
	public function toPsrRequest(): ServerRequestInterface;
}
