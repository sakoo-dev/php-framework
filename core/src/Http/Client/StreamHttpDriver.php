<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\HttpFactory;

/**
 * HttpDriverInterface adapter backed by stream_context_create + file_get_contents.
 *
 * Used under PHP-FPM where Swoole coroutines are unavailable. Supports all
 * HTTP methods via the stream context 'method' option. Parses the HTTP status
 * line from $http_response_header after a successful file_get_contents call
 * and builds a PSR-7 Response via HttpFactory.
 *
 * @throws HttpClientException when file_get_contents returns false
 */
final class StreamHttpDriver implements HttpDriverInterface
{
	public function __construct(
		private readonly HttpFactory $factory,
		private readonly float $timeout = 3.0,
		/** @var array<string, string> */
		private readonly array $defaultHeaders = [],
	) {}

	/**
	 * Sends the PSR-7 request using PHP stream contexts and returns a PSR-7 response.
	 *
	 * @throws HttpClientException
	 */
	public function send(RequestInterface $request): ResponseInterface
	{
		$headers = $this->defaultHeaders;

		foreach ($request->getHeaders() as $name => $values) {
			$headers[$name] = implode(', ', $values);
		}

		$headerLines = array_map(
			static fn (string $name, string $value): string => "{$name}: {$value}",
			array_keys($headers),
			array_values($headers),
		);

		$body = (string) $request->getBody();

		$context = stream_context_create([
			'http' => [
				'method' => $request->getMethod(),
				'header' => implode("\r\n", $headerLines),
				'content' => '' !== $body ? $body : null,
				'timeout' => $this->timeout,
				'ignore_errors' => true,
			],
		]);

		$uri = (string) $request->getUri();
		$rawBody = @file_get_contents($uri, false, $context);

		if (false === $rawBody) {
			throw new HttpClientException("Stream HTTP request failed for {$uri}", $request);
		}

		/**
		 * $http_response_header is populated by file_get_contents as a side-effect.
		 * PHPStan types it as string[] but it is not declared in scope, so we
		 * suppress the undefined-variable notice and cast defensively at runtime.
		 *
		 * @phpstan-ignore variable.undefined
		 */
		$responseHeaders = $http_response_header;

		$statusCode = $this->parseStatusCode($responseHeaders);
		$response = $this->factory->createResponse($statusCode);

		foreach ($this->parseHeaders($responseHeaders) as $name => $value) {
			$response = $response->withHeader($name, $value);
		}

		return $response->withBody($this->factory->createStream($rawBody));
	}

	/**
	 * Extracts the HTTP status code from the first $http_response_header line.
	 *
	 * @param string[] $headers
	 */
	private function parseStatusCode(array $headers): int
	{
		$statusLine = $headers[0] ?? 'HTTP/1.1 200 OK';

		if (preg_match('/HTTP\/\S+\s+(\d{3})/', $statusLine, $m)) {
			return (int) $m[1];
		}

		return 200;
	}

	/**
	 * Converts raw header lines (skipping the status line) into a name→value map.
	 *
	 * @param string[] $headers
	 *
	 * @return array<string, string>
	 */
	private function parseHeaders(array $headers): array
	{
		$parsed = [];

		foreach (array_slice($headers, 1) as $line) {
			if (!str_contains($line, ':')) {
				continue;
			}

			[$name, $value] = explode(':', $line, 2);
			$parsed[trim($name)] = trim($value);
		}

		return $parsed;
	}
}
