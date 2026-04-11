<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Transport\Swoole;

use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\Transport\ResponseEmitter;
use Swoole\Http\Response as SwooleResponse;

/**
 * Emits a PSR-7 response through a Swoole HTTP response object.
 *
 * Translates status code, headers, and body from the immutable PSR-7
 * response into the Swoole response API. The Swoole response object is
 * provided per-request by the Swoole HTTP server event loop.
 */
class SwooleResponseEmitter implements ResponseEmitter
{
	public function __construct(
		private readonly SwooleResponse $swooleResponse,
	) {}

	public function emit(ResponseInterface $response): void
	{
		$this->swooleResponse->status($response->getStatusCode(), $response->getReasonPhrase());

		foreach ($response->getHeaders() as $name => $values) {
			foreach ($values as $value) {
				$this->swooleResponse->header($name, $value);
			}
		}

		$body = $response->getBody();

		if ($body->isSeekable()) {
			$body->rewind();
		}

		$this->swooleResponse->end($body->getContents());
	}
}
