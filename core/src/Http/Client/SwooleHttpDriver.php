<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\HttpFactory;
use Swoole\Coroutine\Http\Client;

/**
 * HttpDriverInterface adapter backed by Swoole\Coroutine\Http\Client.
 *
 * Wraps (does not subclass) the Swoole coroutine HTTP client. Parses the
 * PSR-7 request URI to extract host, port, and path, forwards all headers,
 * executes the request inside the running coroutine, and builds a PSR-7
 * Response via HttpFactory.
 *
 * Only used when PHP_SAPI === 'cli' (Swoole server context). Never import
 * this class in FPM-only code paths — always depend on HttpDriverInterface.
 *
 * @throws HttpClientException when the Swoole client returns statusCode -1
 *                             (connection refused or DNS failure)
 */
final class SwooleHttpDriver implements HttpDriverInterface
{
	public function __construct(
		private readonly HttpFactory $factory,
		private readonly float $timeout = 3.0,
		/** @var array<string, string> */
		private readonly array $defaultHeaders = [],
	) {}

	/**
	 * Sends the PSR-7 request through a Swoole coroutine HTTP client and
	 * returns a PSR-7 response.
	 *
	 * @throws HttpClientException
	 */
	public function send(RequestInterface $request): ResponseInterface
	{
		$uri = $request->getUri();
		$host = $uri->getHost();
		$port = $uri->getPort() ?? ('https' === $uri->getScheme() ? 443 : 80);
		$ssl = 'https' === $uri->getScheme();

		$path = $uri->getPath();

		if ('' === $path) {
			$path = '/';
		}

		$query = $uri->getQuery();

		if ('' !== $query) {
			$path .= '?' . $query;
		}

		/** @phpstan-ignore class.notFound */
		$client = new Client($host, $port, $ssl);
		$client->set(['timeout' => $this->timeout]);

		$headers = $this->defaultHeaders;

		foreach ($request->getHeaders() as $name => $values) {
			$headers[$name] = implode(', ', $values);
		}

		$client->setHeaders($headers);
		$client->setMethod($request->getMethod());

		$body = (string) $request->getBody();

		if ('' !== $body) {
			$client->setData($body);
		}

		$client->execute($path);

		/** @phpstan-ignore property.notFound, cast.int */
		$statusCode = (int) $client->statusCode;

		if (-1 === $statusCode) {
			/** @phpstan-ignore property.notFound */
			$errMsg = is_string($client->errMsg) ? $client->errMsg : 'unknown error';

			throw new HttpClientException(
				"Swoole HTTP connection failed to {$host}:{$port} — {$errMsg}",
				$request,
			);
		}

		$response = $this->factory->createResponse($statusCode);

		/** @phpstan-ignore property.notFound */
		$rawHeaders = $client->headers;

		if (is_array($rawHeaders)) {
			/** @var array<mixed, mixed> $rawHeaders */
			foreach ($rawHeaders as $name => $value) {
				$response = $response->withHeader((string) $name, is_string($value) ? $value : '');
			}
		}

		/** @phpstan-ignore property.notFound */
		$rawBody = $client->body;

		return $response->withBody(
			$this->factory->createStream(is_string($rawBody) ? $rawBody : ''),
		);
	}
}
