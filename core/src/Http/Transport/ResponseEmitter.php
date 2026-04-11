<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Transport;

use Psr\Http\Message\ResponseInterface;

/**
 * Writes a PSR-7 response back through the active transport layer.
 *
 * Each transport (Swoole, FPM) implements this contract to translate the
 * immutable PSR-7 response into the runtime-specific output mechanism
 * (header() + echo for FPM, $swooleResponse->write() for Swoole, etc.).
 */
interface ResponseEmitter
{
	/**
	 * Emits the PSR-7 response to the client via the active transport.
	 */
	public function emit(ResponseInterface $response): void;
}
