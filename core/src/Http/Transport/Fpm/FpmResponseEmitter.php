<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Transport\Fpm;

use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\Transport\ResponseEmitter;

/**
 * Emits a PSR-7 response via PHP-FPM's header() and output stream.
 *
 * Sends the status line, all headers, and the body content using standard
 * PHP output functions. This emitter must only be used in FPM/CGI contexts
 * where header() is available.
 */
class FpmResponseEmitter implements ResponseEmitter
{
	public function emit(ResponseInterface $response): void
	{
		$this->emitStatusLine($response);
		$this->emitHeaders($response);
		$this->emitBody($response);
	}

	private function emitStatusLine(ResponseInterface $response): void
	{
		$statusLine = sprintf(
			'HTTP/%s %d %s',
			$response->getProtocolVersion(),
			$response->getStatusCode(),
			$response->getReasonPhrase(),
		);

		header($statusLine, true, $response->getStatusCode());
	}

	private function emitHeaders(ResponseInterface $response): void
	{
		foreach ($response->getHeaders() as $name => $values) {
			$replace = 'set-cookie' !== mb_strtolower($name);

			foreach ($values as $value) {
				header("$name: $value", $replace, $response->getStatusCode());
				$replace = false;
			}
		}
	}

	private function emitBody(ResponseInterface $response): void
	{
		$body = $response->getBody();

		if ($body->isSeekable()) {
			$body->rewind();
		}

		while (!$body->eof()) {
			echo $body->read(8192);
		}
	}
}
