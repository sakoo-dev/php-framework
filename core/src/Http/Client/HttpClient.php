<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\HttpFactory;

/**
 * PSR-compliant outbound HTTP client with an immutable fluent API.
 *
 * Delegates transport to the injected HttpDriverInterface so the same facade
 * works under Swoole (SwooleHttpDriver) and PHP-FPM (StreamHttpDriver) without
 * any change to call sites.
 *
 * Convenience methods (get, post, put, patch, delete) build a PSR-7 Request
 * via HttpFactory and forward it to the driver. withTimeout() and withHeader()
 * return new instances — this class is immutable.
 */
final class HttpClient implements HttpClientInterface
{
	/**
	 * @param array<string, string> $headers
	 */
	public function __construct(
		private readonly HttpDriverInterface $driver,
		private readonly HttpFactory $factory,
		private readonly float $timeout = 3.0,
		private readonly array $headers = [],
	) {}

	/**
	 * Sends the given PSR-7 request and returns the PSR-7 response.
	 *
	 * @throws HttpClientException
	 */
	public function send(RequestInterface $request): ResponseInterface
	{
		foreach ($this->headers as $name => $value) {
			$request = $request->withHeader($name, $value);
		}

		return $this->driver->send($request);
	}

	/**
	 * Sends a GET request to $uri with optional extra headers.
	 *
	 * @param array<string, string> $headers
	 *
	 * @throws HttpClientException
	 */
	public function get(string $uri, array $headers = []): ResponseInterface
	{
		return $this->send($this->buildRequest('GET', $uri, '', $headers));
	}

	/**
	 * Sends a POST request with $body to $uri.
	 *
	 * @param array<string, string> $headers
	 *
	 * @throws HttpClientException
	 */
	public function post(string $uri, string $body, array $headers = []): ResponseInterface
	{
		return $this->send($this->buildRequest('POST', $uri, $body, $headers));
	}

	/**
	 * Sends a PUT request with $body to $uri.
	 *
	 * @param array<string, string> $headers
	 *
	 * @throws HttpClientException
	 */
	public function put(string $uri, string $body, array $headers = []): ResponseInterface
	{
		return $this->send($this->buildRequest('PUT', $uri, $body, $headers));
	}

	/**
	 * Sends a PATCH request with $body to $uri.
	 *
	 * @param array<string, string> $headers
	 *
	 * @throws HttpClientException
	 */
	public function patch(string $uri, string $body, array $headers = []): ResponseInterface
	{
		return $this->send($this->buildRequest('PATCH', $uri, $body, $headers));
	}

	/**
	 * Sends a DELETE request to $uri with optional extra headers.
	 *
	 * @param array<string, string> $headers
	 *
	 * @throws HttpClientException
	 */
	public function delete(string $uri, array $headers = []): ResponseInterface
	{
		return $this->send($this->buildRequest('DELETE', $uri, '', $headers));
	}

	/**
	 * Returns a new instance with the given timeout in seconds.
	 */
	public function withTimeout(float $seconds): static
	{
		return new self($this->driver, $this->factory, $seconds, $this->headers);
	}

	/**
	 * Returns a new instance with $name: $value added to the default headers.
	 */
	public function withHeader(string $name, string $value): static
	{
		$headers = $this->headers;
		$headers[$name] = $value;

		return new static($this->driver, $this->factory, $this->timeout, $headers);
	}

	/**
	 * Builds a PSR-7 Request from method, URI, body, and per-call headers.
	 *
	 * @param array<string, string> $extraHeaders
	 */
	private function buildRequest(string $method, string $uri, string $body, array $extraHeaders): RequestInterface
	{
		$request = $this->factory->createRequest($method, $uri);

		if ('' !== $body) {
			$request = $request->withBody($this->factory->createStream($body));
		}

		foreach ($extraHeaders as $name => $value) {
			$request = $request->withHeader($name, $value);
		}

		return $request;
	}
}
