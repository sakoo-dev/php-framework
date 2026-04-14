<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Immutable PSR-7 incoming server-side HTTP request.
 *
 * Extends Request with server parameters, cookies, query parameters, uploaded
 * files, parsed body, and arbitrary request attributes. Every with*() mutator
 * returns a new instance.
 */
final class ServerRequest extends Request implements ServerRequestInterface
{
	/**
	 * @param array<string, mixed>     $serverParams
	 * @param array<string, string>    $cookieParams
	 * @param array<string, mixed>     $queryParams
	 * @param array<mixed>             $uploadedFiles
	 * @param null|array<mixed>|object $parsedBody
	 * @param array<string, mixed>     $attributes
	 */
	public function __construct(
		string $method,
		UriInterface $uri,
		HeaderBag $headers,
		StreamInterface $body,
		string $protocolVersion = '1.1',
		private readonly array $serverParams = [],
		private readonly array $cookieParams = [],
		private readonly array $queryParams = [],
		private readonly array $uploadedFiles = [],
		private readonly array|object|null $parsedBody = null,
		private readonly array $attributes = [],
	) {
		parent::__construct($method, $uri, $headers, $body, $protocolVersion);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getServerParams(): array
	{
		return $this->serverParams;
	}

	/**
	 * @return array<string, string>
	 */
	public function getCookieParams(): array
	{
		return $this->cookieParams;
	}

	/**
	 * @param array<string, string> $cookies
	 */
	public function withCookieParams(array $cookies): ServerRequestInterface
	{
		return $this->rebuild(cookieParams: $cookies);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getQueryParams(): array
	{
		return $this->queryParams;
	}

	/**
	 * @param array<string, mixed> $query
	 */
	public function withQueryParams(array $query): ServerRequestInterface
	{
		return $this->rebuild(queryParams: $query);
	}

	/**
	 * @return array<mixed>
	 */
	public function getUploadedFiles(): array
	{
		return $this->uploadedFiles;
	}

	/**
	 * @param array<mixed> $uploadedFiles
	 */
	public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
	{
		return $this->rebuild(uploadedFiles: $uploadedFiles);
	}

	/**
	 * @return null|array<mixed>|object
	 */
	public function getParsedBody(): array|object|null
	{
		return $this->parsedBody;
	}

	/**
	 * @param null|array<mixed>|object $data
	 */
	public function withParsedBody($data): ServerRequestInterface
	{
		$body = (is_array($data) || is_object($data)) ? $data : null;

		return $this->rebuild(parsedBody: $body, parsedBodyProvided: true);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function getAttribute(string $name, $default = null): mixed
	{
		return $this->attributes[$name] ?? $default;
	}

	public function withAttribute(string $name, $value): ServerRequestInterface
	{
		$attributes = $this->attributes;
		$attributes[$name] = $value;

		return $this->rebuild(attributes: $attributes);
	}

	public function withoutAttribute(string $name): ServerRequestInterface
	{
		$attributes = $this->attributes;
		unset($attributes[$name]);

		return $this->rebuild(attributes: $attributes);
	}

	protected function cloneWith(
		?string $protocolVersion = null,
		?HeaderBag $headers = null,
		?StreamInterface $body = null,
	): static {
		return new self(
			$this->getMethod(),
			$this->getUri(),
			$headers ?? $this->getHeaderBag(),
			$body ?? $this->getBody(),
			$protocolVersion ?? $this->getProtocolVersion(),
			$this->serverParams,
			$this->cookieParams,
			$this->queryParams,
			$this->uploadedFiles,
			$this->parsedBody,
			$this->attributes,
		);
	}

	/**
	 * @param null|array<string, string> $cookieParams
	 * @param null|array<string, mixed>  $queryParams
	 * @param null|array<mixed>          $uploadedFiles
	 * @param null|array<mixed>|object   $parsedBody
	 * @param null|array<string, mixed>  $attributes
	 */
	private function rebuild(
		?array $cookieParams = null,
		?array $queryParams = null,
		?array $uploadedFiles = null,
		array|object|null $parsedBody = null,
		bool $parsedBodyProvided = false,
		?array $attributes = null,
	): self {
		return new self(
			$this->getMethod(),
			$this->getUri(),
			$this->getHeaderBag(),
			$this->getBody(),
			$this->getProtocolVersion(),
			$this->serverParams,
			$cookieParams ?? $this->cookieParams,
			$queryParams ?? $this->queryParams,
			$uploadedFiles ?? $this->uploadedFiles,
			$parsedBodyProvided ? $parsedBody : $this->parsedBody,
			$attributes ?? $this->attributes,
		);
	}
}
