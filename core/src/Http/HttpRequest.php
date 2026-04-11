<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Developer-friendly wrapper around a PSR-7 ServerRequestInterface.
 *
 * Provides typed, convenient accessors for every part of an HTTP request
 * (input, query, headers, cookies, files, path parameters, method, URL)
 * without requiring the developer to work with the PSR interfaces directly.
 *
 * The underlying PSR-7 request is always available via psrRequest() for
 * cases where the raw interface is needed (e.g. middleware interop).
 *
 * HttpRequest is immutable (readonly) — the withPsr() and withAttribute()
 * methods return new instances wrapping the modified PSR-7 request.
 */
final readonly class HttpRequest
{
	public function __construct(
		private ServerRequestInterface $request,
	) {}

	/**
	 * Returns the underlying PSR-7 ServerRequestInterface.
	 */
	public function psrRequest(): ServerRequestInterface
	{
		return $this->request;
	}

	/**
	 * Returns a new HttpRequest wrapping a different PSR-7 request. Used in
	 * middleware to forward a modified request down the pipeline while staying
	 * in the HttpRequest world.
	 */
	public function withPsr(ServerRequestInterface $request): self
	{
		return new self($request);
	}

	/**
	 * Returns a new HttpRequest with an added request attribute. Convenience
	 * shorthand for withPsr($request->psrRequest()->withAttribute(...)).
	 */
	public function withAttribute(string $name, mixed $value): self
	{
		return new self($this->request->withAttribute($name, $value));
	}

	/**
	 * Returns the HTTP method (GET, POST, PUT, etc.).
	 */
	public function method(): string
	{
		return $this->request->getMethod();
	}

	/**
	 * Returns true when the request method matches $method (case-insensitive).
	 */
	public function isMethod(string $method): bool
	{
		return mb_strtoupper($method) === $this->request->getMethod();
	}

	/**
	 * Returns the request path without query string (e.g. "/users/42").
	 */
	public function path(): string
	{
		return $this->request->getUri()->getPath();
	}

	/**
	 * Returns the full URL including scheme, host, path, and query string.
	 */
	public function url(): string
	{
		return (string) $this->request->getUri();
	}

	/**
	 * Returns the URI scheme ("http" or "https").
	 */
	public function scheme(): string
	{
		return $this->request->getUri()->getScheme();
	}

	/**
	 * Returns the host name from the URI (e.g. "example.com").
	 */
	public function host(): string
	{
		return $this->request->getUri()->getHost();
	}

	/**
	 * Returns the port number, or null when using a standard port.
	 */
	public function port(): ?int
	{
		return $this->request->getUri()->getPort();
	}

	/**
	 * Returns true when the request was made over HTTPS.
	 */
	public function isSecure(): bool
	{
		return 'https' === $this->request->getUri()->getScheme();
	}

	/**
	 * Returns a value from the parsed body (POST data), falling back to
	 * the query string, then to $default.
	 */
	public function input(string $key, mixed $default = null): mixed
	{
		$body = $this->request->getParsedBody();

		if (is_array($body) && array_key_exists($key, $body)) {
			return $body[$key];
		}

		return $this->query($key, $default);
	}

	/**
	 * Returns all input data merged from the parsed body and query parameters.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		/** @var array<string, mixed> $query */
		$query = $this->request->getQueryParams();
		$body = $this->request->getParsedBody();

		if (is_array($body)) {
			// @phpstan-ignore-next-line
			return array_merge($query, $body);
		}

		return $query;
	}

	/**
	 * Returns true when the input key exists in parsed body or query params.
	 */
	public function has(string $key): bool
	{
		$body = $this->request->getParsedBody();

		if (is_array($body) && array_key_exists($key, $body)) {
			return true;
		}

		return array_key_exists($key, $this->request->getQueryParams());
	}

	/**
	 * Returns a query string parameter by name, or $default when absent.
	 */
	public function query(string $key, mixed $default = null): mixed
	{
		return $this->request->getQueryParams()[$key] ?? $default;
	}

	/**
	 * Returns all query string parameters.
	 *
	 * @return array<string, mixed>
	 */
	public function queryAll(): array
	{
		// @phpstan-ignore-next-line
		return $this->request->getQueryParams();
	}

	/**
	 * Returns a header value as a single string, or $default when absent.
	 */
	public function header(string $name, string $default = ''): string
	{
		if (!$this->request->hasHeader($name)) {
			return $default;
		}

		return $this->request->getHeaderLine($name);
	}

	/**
	 * Returns all values for a given header as an array.
	 *
	 * @return string[]
	 */
	public function headers(string $name): array
	{
		return $this->request->getHeader($name);
	}

	/**
	 * Returns true when the request has the given header.
	 */
	public function hasHeader(string $name): bool
	{
		return $this->request->hasHeader($name);
	}

	/**
	 * Returns the Bearer token from the Authorization header, or null
	 * when no Bearer token is present.
	 */
	public function bearerToken(): ?string
	{
		$header = $this->header('Authorization');

		if (str_starts_with($header, 'Bearer ')) {
			return substr($header, 7);
		}

		return null;
	}

	/**
	 * Returns a cookie value by name, or $default when absent.
	 */
	public function cookie(string $name, string $default = ''): string
	{
		/** @var array<string, string> $cookies */
		$cookies = $this->request->getCookieParams();

		return $cookies[$name] ?? $default;
	}

	/**
	 * Returns all cookies as a key-value array.
	 *
	 * @return array<string, string>
	 */
	public function cookies(): array
	{
		// @phpstan-ignore-next-line
		return $this->request->getCookieParams();
	}

	/**
	 * Returns a route parameter (request attribute) by name, or $default.
	 */
	public function routeParam(string $name, mixed $default = null): mixed
	{
		return $this->request->getAttribute($name, $default);
	}

	/**
	 * Returns all route parameters (request attributes).
	 *
	 * @return array<string, mixed>
	 */
	public function routeParams(): array
	{
		// @phpstan-ignore-next-line
		return $this->request->getAttributes();
	}

	/**
	 * Returns a single uploaded file by form field name, or null when absent.
	 */
	public function file(string $name): ?UploadedFileInterface
	{
		$files = $this->request->getUploadedFiles();

		if (isset($files[$name]) && $files[$name] instanceof UploadedFileInterface) {
			return $files[$name];
		}

		return null;
	}

	/**
	 * Returns all uploaded files.
	 *
	 * @return array<mixed>
	 */
	public function files(): array
	{
		return $this->request->getUploadedFiles();
	}

	/**
	 * Returns true when the uploaded file for the given field exists.
	 */
	public function hasFile(string $name): bool
	{
		return null !== $this->file($name);
	}

	/**
	 * Returns the raw request body as a string.
	 */
	public function body(): string
	{
		return (string) $this->request->getBody();
	}

	/**
	 * Decodes the request body as JSON and returns the result.
	 *
	 * @return array<string, mixed>
	 *
	 * @throws \JsonException
	 */
	public function json(): array
	{
		// @phpstan-ignore-next-line
		return json_decode($this->body(), true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * Returns the Content-Type header value, or an empty string.
	 */
	public function contentType(): string
	{
		return $this->header('Content-Type');
	}

	/**
	 * Returns true when the Content-Type indicates a JSON payload.
	 */
	public function isJson(): bool
	{
		return str_contains($this->contentType(), 'application/json');
	}

	/**
	 * Returns the client IP address from the server parameters.
	 */
	public function ip(): string
	{
		$forwarded = $this->header('X-Forwarded-For');

		if ('' !== $forwarded) {
			$ips = explode(',', $forwarded);

			return trim($ips[0]);
		}

		$serverParams = $this->request->getServerParams();

		if (isset($serverParams['REMOTE_ADDR']) && is_string($serverParams['REMOTE_ADDR'])) {
			return $serverParams['REMOTE_ADDR'];
		}

		return '';
	}

	/**
	 * Returns the value of the User-Agent header, or an empty string.
	 */
	public function userAgent(): string
	{
		return $this->header('User-Agent');
	}

	/**
	 * Returns a server parameter by key, or $default.
	 */
	public function server(string $key, mixed $default = null): mixed
	{
		return $this->request->getServerParams()[$key] ?? $default;
	}
}
