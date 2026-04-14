<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\UriInterface;

/**
 * Immutable PSR-7 URI value object implementing RFC 3986.
 *
 * Each with*() method returns a new instance, leaving the original unchanged.
 * Scheme and host are normalised to lowercase on storage. Standard ports
 * (80 for http, 443 for https) are omitted from getAuthority() and __toString()
 * output as recommended by the specification.
 */
final class Uri implements UriInterface
{
	private const STANDARD_PORTS = [
		'http' => 80,
		'https' => 443,
	];

	public function __construct(
		private readonly string $scheme = '',
		private readonly string $userInfo = '',
		private readonly string $host = '',
		private readonly ?int $port = null,
		private readonly string $path = '',
		private readonly string $query = '',
		private readonly string $fragment = '',
	) {}

	/**
	 * Parses a URI string into its components and returns a new Uri instance.
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function fromString(string $uri): self
	{
		if ('' === $uri) {
			return new self();
		}

		$parts = parse_url($uri);

		if (false === $parts) {
			throw new \InvalidArgumentException("Unable to parse URI: $uri");
		}

		$user = $parts['user'] ?? '';
		$pass = $parts['pass'] ?? null;
		$userInfo = $user;

		if (null !== $pass) {
			$userInfo .= ':' . $pass;
		}

		return new self(
			scheme: isset($parts['scheme']) ? mb_strtolower($parts['scheme']) : '',
			userInfo: $userInfo,
			host: isset($parts['host']) ? mb_strtolower($parts['host']) : '',
			port: $parts['port'] ?? null,
			path: $parts['path'] ?? '',
			query: $parts['query'] ?? '',
			fragment: $parts['fragment'] ?? '',
		);
	}

	public function getScheme(): string
	{
		return $this->scheme;
	}

	public function getAuthority(): string
	{
		if ('' === $this->host) {
			return '';
		}

		$authority = $this->host;

		if ('' !== $this->userInfo) {
			$authority = $this->userInfo . '@' . $authority;
		}

		if (null !== $this->port && !$this->isStandardPort()) {
			$authority .= ':' . $this->port;
		}

		return $authority;
	}

	public function getUserInfo(): string
	{
		return $this->userInfo;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): ?int
	{
		if ($this->isStandardPort()) {
			return null;
		}

		return $this->port;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getQuery(): string
	{
		return $this->query;
	}

	public function getFragment(): string
	{
		return $this->fragment;
	}

	public function withScheme(string $scheme): UriInterface
	{
		return new self(mb_strtolower($scheme), $this->userInfo, $this->host, $this->port, $this->path, $this->query, $this->fragment);
	}

	public function withUserInfo(string $user, ?string $password = null): UriInterface
	{
		$info = $user;

		if (null !== $password && '' !== $password) {
			$info .= ':' . $password;
		}

		return new self($this->scheme, $info, $this->host, $this->port, $this->path, $this->query, $this->fragment);
	}

	public function withHost(string $host): UriInterface
	{
		return new self($this->scheme, $this->userInfo, mb_strtolower($host), $this->port, $this->path, $this->query, $this->fragment);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function withPort(?int $port): UriInterface
	{
		if (null !== $port && ($port < 0 || $port > 65535)) {
			throw new \InvalidArgumentException("Invalid port: $port. Must be between 0 and 65535.");
		}

		return new self($this->scheme, $this->userInfo, $this->host, $port, $this->path, $this->query, $this->fragment);
	}

	public function withPath(string $path): UriInterface
	{
		return new self($this->scheme, $this->userInfo, $this->host, $this->port, $path, $this->query, $this->fragment);
	}

	public function withQuery(string $query): UriInterface
	{
		return new self($this->scheme, $this->userInfo, $this->host, $this->port, $this->path, $query, $this->fragment);
	}

	public function withFragment(string $fragment): UriInterface
	{
		return new self($this->scheme, $this->userInfo, $this->host, $this->port, $this->path, $this->query, $fragment);
	}

	public function __toString(): string
	{
		$uri = '';

		if ('' !== $this->scheme) {
			$uri .= $this->scheme . ':';
		}

		$authority = $this->getAuthority();

		if ('' !== $authority) {
			$uri .= '//' . $authority;
		}

		$path = $this->path;

		if ('' !== $authority && ('' === $path || '/' !== $path[0])) {
			$path = '/' . $path;
		} elseif ('' === $authority && str_starts_with($path, '//')) {
			$path = '/' . ltrim($path, '/');
		}

		$uri .= $path;

		if ('' !== $this->query) {
			$uri .= '?' . $this->query;
		}

		if ('' !== $this->fragment) {
			$uri .= '#' . $this->fragment;
		}

		return $uri;
	}

	private function isStandardPort(): bool
	{
		if (null === $this->port) {
			return true;
		}

		$standardPort = self::STANDARD_PORTS[$this->scheme] ?? null;

		return $this->port === $standardPort;
	}
}
