<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Http;

use NeuronAI\HttpClient\GuzzleHttpClient;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\HttpClient\HttpResponse;
use NeuronAI\HttpClient\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * HTTP client decorator that logs every request, response, and error produced
 * by the inner GuzzleHttpClient.
 *
 * Each log entry records the HTTP method, URI, response status, and elapsed
 * time in milliseconds. Errors include the exception class and message so that
 * stream failures (e.g. 429, 5xx from GapGPT) are visible in the AI log file
 * rather than appearing as a bare "error" event.
 *
 * Usage — inject via AIModel::getHttpClient() or directly into a provider:
 *
 *   $httpClient = new LoggingHttpClient(resolve('logger.ai'));
 *   new GapGpt(model: '...', httpClient: $httpClient);
 */
final class LoggingHttpClient implements HttpClientInterface
{
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly HttpClientInterface $inner = new GuzzleHttpClient(),
	) {}

	public function request(HttpRequest $request): HttpResponse
	{
		$start = hrtime(true);

		try {
			$response = $this->inner->request($request);
			$this->logSuccess('http-request', $request, $response->statusCode, $start);

			return $response;
		} catch (\Throwable $e) {
			$this->logError('http-request', $request, $e, $start);

			throw $e;
		}
	}

	public function stream(HttpRequest $request): StreamInterface
	{
		$start = hrtime(true);

		try {
			$stream = $this->inner->stream($request);
			$this->logSuccess('http-stream', $request, 200, $start);

			return $stream;
		} catch (\Throwable $e) {
			$this->logError('http-stream', $request, $e, $start);

			throw $e;
		}
	}

	public function withBaseUri(string $baseUri): self
	{
		return new self($this->logger, $this->inner->withBaseUri($baseUri));
	}

	public function withHeaders(array $headers): self
	{
		return new self($this->logger, $this->inner->withHeaders($headers));
	}

	public function withTimeout(float $timeout): self
	{
		return new self($this->logger, $this->inner->withTimeout($timeout));
	}

	private function logSuccess(string $event, HttpRequest $request, int $status, int $startHrtime): void
	{
		$this->logger->log(LogLevel::DEBUG, $event, [
			'method' => $request->method->value,
			'uri' => $request->uri,
			'status' => $status,
			'elapsed_ms' => $this->elapsedMs($startHrtime),
		]);
	}

	private function logError(string $event, HttpRequest $request, \Throwable $e, int $startHrtime): void
	{
		$this->logger->log(LogLevel::ERROR, $event . '-error', [
			'method' => $request->method->value,
			'uri' => $request->uri,
			'exception' => $e::class,
			'error' => $e->getMessage(),
			'elapsed_ms' => $this->elapsedMs($startHrtime),
		]);
	}

	private function elapsedMs(int $startHrtime): int
	{
		return (int) ((hrtime(true) - $startHrtime) / 1_000_000);
	}
}
