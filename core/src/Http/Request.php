<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Immutable PSR-7 HTTP request message.
 *
 * Represents an outgoing, client-side request. The Host header is
 * synchronised from the URI on construction when no Host header is present,
 * and on withUri() unless $preserveHost is true and a Host header already
 * exists.
 */
class Request extends Message implements RequestInterface
{
	public function __construct(
		private readonly string $method,
		private readonly UriInterface $uri,
		HeaderBag $headers,
		StreamInterface $body,
		string $protocolVersion = '1.1',
		private readonly string $requestTarget = '',
	) {
		if (!$headers->has('Host') || '' === $headers->getLine('Host')) {
			$headers = $this->syncHostHeader($headers, $uri);
		}

		parent::__construct($protocolVersion, $headers, $body);
	}

	public function getRequestTarget(): string
	{
		if ('' !== $this->requestTarget) {
			return $this->requestTarget;
		}

		$target = $this->uri->getPath();

		if ('' === $target) {
			$target = '/';
		}

		$query = $this->uri->getQuery();

		if ('' !== $query) {
			$target .= '?' . $query;
		}

		return $target;
	}

	public function withRequestTarget(string $requestTarget): RequestInterface
	{
		return new static( // @phpstan-ignore new.static
			$this->method, $this->uri, $this->getHeaderBag(), $this->getBody(),
			$this->getProtocolVersion(), $requestTarget,
		);
	}

	public function getMethod(): string
	{
		return $this->method;
	}

	public function withMethod(string $method): RequestInterface
	{
		return new static( // @phpstan-ignore new.static
			$method, $this->uri, $this->getHeaderBag(), $this->getBody(),
			$this->getProtocolVersion(), $this->requestTarget,
		);
	}

	public function getUri(): UriInterface
	{
		return $this->uri;
	}

	public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
	{
		$headers = $this->getHeaderBag();

		if (!$preserveHost || !$headers->has('Host') || '' === $headers->getLine('Host')) {
			$headers = $this->syncHostHeader($headers, $uri);
		}

		return new static( // @phpstan-ignore new.static
			$this->method, $uri, $headers, $this->getBody(),
			$this->getProtocolVersion(), $this->requestTarget,
		);
	}

	protected function cloneWith(
		?string $protocolVersion = null,
		?HeaderBag $headers = null,
		?StreamInterface $body = null,
	): static {
		return new static( // @phpstan-ignore new.static
			$this->method, $this->uri, $headers ?? $this->getHeaderBag(),
			$body ?? $this->getBody(), $protocolVersion ?? $this->getProtocolVersion(),
			$this->requestTarget,
		);
	}

	private function syncHostHeader(HeaderBag $headers, UriInterface $uri): HeaderBag
	{
		$host = $uri->getHost();

		if ('' === $host) {
			return $headers;
		}

		$port = $uri->getPort();

		if (null !== $port) {
			$host .= ':' . $port;
		}

		return $headers->withHeader('Host', $host);
	}
}
