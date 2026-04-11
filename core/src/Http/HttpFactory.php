<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Unified PSR-17 HTTP factory implementing all six factory interfaces.
 *
 * Provides a single class for creating PSR-7 value objects. Bind it against
 * each individual PSR-17 interface in the ServiceLoader so consumers can
 * depend on any granularity they need.
 */
class HttpFactory implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface, UploadedFileFactoryInterface, UriFactoryInterface
{
	/**
	 * @param string|UriInterface $uri
	 */
	public function createRequest(string $method, $uri): RequestInterface
	{
		$uriInstance = $uri instanceof UriInterface ? $uri : Uri::fromString((string) $uri);

		return new Request(
			$method,
			$uriInstance,
			new HeaderBag(),
			Stream::createFromString(),
		);
	}

	public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
	{
		return new Response($code, $reasonPhrase);
	}

	/**
	 * @param string|UriInterface  $uri
	 * @param array<string, mixed> $serverParams
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
	{
		$uriInstance = $uri instanceof UriInterface ? $uri : Uri::fromString((string) $uri);

		return new ServerRequest(
			$method,
			$uriInstance,
			new HeaderBag(),
			Stream::createFromString(),
			'1.1',
			$serverParams,
		);
	}

	public function createStream(string $content = ''): StreamInterface
	{
		return Stream::createFromString($content);
	}

	/**
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
	{
		$resource = @fopen($filename, $mode);

		if (false === $resource) {
			throw new \RuntimeException("Unable to open file: $filename");
		}

		return Stream::create($resource);
	}

	/**
	 * @param resource $resource
	 */
	public function createStreamFromResource($resource): StreamInterface
	{
		return Stream::create($resource);
	}

	public function createUploadedFile(
		StreamInterface $stream,
		?int $size = null,
		int $error = UPLOAD_ERR_OK,
		?string $clientFilename = null,
		?string $clientMediaType = null,
	): UploadedFileInterface {
		return new UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function createUri(string $uri = ''): UriInterface
	{
		return Uri::fromString($uri);
	}
}
