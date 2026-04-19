<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Internal transport driver port used by HttpClient.
 *
 * Concrete implementations wrap Swoole's coroutine HTTP client or PHP's
 * stream_context_create, keeping transport concerns isolated from the
 * HttpClient facade. Not exposed to application code — always resolve
 * HttpClientInterface from the container.
 */
interface HttpDriverInterface
{
	/**
	 * Executes the request using the underlying transport and returns a PSR-7 response.
	 *
	 * @throws HttpClientException on connection failure or transport error
	 */
	public function send(RequestInterface $request): ResponseInterface;
}
