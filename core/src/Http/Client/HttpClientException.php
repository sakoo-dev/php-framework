<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Client;

use Psr\Http\Message\RequestInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when an outbound HTTP request fails at the transport layer.
 *
 * Carries the originating request so callers can inspect the URI, method,
 * and headers when logging or retrying without re-constructing the context.
 */
final class HttpClientException extends Exception
{
	public function __construct(
		string $message,
		private readonly RequestInterface $request,
		int $code = 0,
		?\Throwable $previous = null,
	) {
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Returns the PSR-7 request that caused the failure.
	 */
	public function getRequest(): RequestInterface
	{
		return $this->request;
	}
}
