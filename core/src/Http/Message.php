<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Base PSR-7 HTTP message providing header and body management.
 *
 * Shared by both Request and Response. Every with*() mutator returns a new
 * instance — the original is never modified. Header storage is delegated to
 * HeaderBag which handles case-insensitive lookup while preserving original
 * casing.
 */
abstract class Message implements MessageInterface
{
	public function __construct(
		private readonly string $protocolVersion,
		private readonly HeaderBag $headers,
		private readonly StreamInterface $body,
	) {}

	public function getProtocolVersion(): string
	{
		return $this->protocolVersion;
	}

	public function withProtocolVersion(string $version): MessageInterface
	{
		return $this->cloneWith(protocolVersion: $version);
	}

	/**
	 * @return array<string, string[]>
	 */
	public function getHeaders(): array
	{
		return $this->headers->all();
	}

	public function hasHeader(string $name): bool
	{
		return $this->headers->has($name);
	}

	/**
	 * @return string[]
	 */
	public function getHeader(string $name): array
	{
		return $this->headers->get($name);
	}

	public function getHeaderLine(string $name): string
	{
		return $this->headers->getLine($name);
	}

	/**
	 * @param string|string[] $value
	 */
	public function withHeader(string $name, $value): MessageInterface
	{
		return $this->cloneWith(headers: $this->headers->withHeader($name, $value));
	}

	/**
	 * @param string|string[] $value
	 */
	public function withAddedHeader(string $name, $value): MessageInterface
	{
		return $this->cloneWith(headers: $this->headers->withAddedHeader($name, $value));
	}

	public function withoutHeader(string $name): MessageInterface
	{
		return $this->cloneWith(headers: $this->headers->withoutHeader($name));
	}

	public function getBody(): StreamInterface
	{
		return $this->body;
	}

	public function withBody(StreamInterface $body): MessageInterface
	{
		return $this->cloneWith(body: $body);
	}

	protected function getHeaderBag(): HeaderBag
	{
		return $this->headers;
	}

	/**
	 * Template method for subclasses to implement clone-with-changes semantics.
	 * Each subclass knows its own constructor shape and can forward unchanged
	 * fields while replacing the ones that differ.
	 */
	abstract protected function cloneWith(
		?string $protocolVersion = null,
		?HeaderBag $headers = null,
		?StreamInterface $body = null,
	): static;
}
