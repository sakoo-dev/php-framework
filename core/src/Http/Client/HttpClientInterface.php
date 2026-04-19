<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Port for outbound HTTP clients.
 *
 * Adapters implement this interface against Swoole coroutine HTTP, PHP streams,
 * or any other transport. Callers depend only on this contract so the driver
 * can be swapped based on SAPI without touching application code.
 */
interface HttpClientInterface
{
	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 *
	 * @throws HttpClientException on connection failure or transport error
	 */
	public function send(RequestInterface $request): ResponseInterface;
}
