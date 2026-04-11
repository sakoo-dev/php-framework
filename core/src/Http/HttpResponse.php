<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Fluent response builder that wraps the immutable PSR-7 Response.
 *
 * Provides developer-friendly factory methods for the most common response
 * types (JSON, plain text, HTML, redirects, no-content) and fluent mutators
 * for headers, cookies, and status codes. Every mutation returns the same
 * HttpResponse instance — it rebuilds the internal PSR-7 Response on each
 * call so the builder itself stays mutable and chainable while the underlying
 * PSR-7 object remains immutable.
 *
 * The final PSR-7 ResponseInterface is obtained via toPsrResponse() and is
 * what the Router returns to the middleware pipeline.
 */
final class HttpResponse
{
	private ResponseInterface $response;

	public function __construct(int $statusCode = 200, string $reasonPhrase = '')
	{
		$this->response = new Response($statusCode, $reasonPhrase);
	}

	/**
	 * Wraps an existing PSR-7 ResponseInterface in an HttpResponse.
	 * Used by the Middleware base class to convert the downstream PSR-7
	 * response back into the fluent builder so middleware can chain
	 * withHeader(), withStatus(), etc.
	 */
	public static function fromPsr(ResponseInterface $response): self
	{
		$instance = new self();
		$instance->response = $response;

		return $instance;
	}

	/**
	 * Creates a JSON response with the given data and status code.
	 *
	 * @param array<mixed>|object $data
	 *
	 * @throws \JsonException
	 */
	public static function json(array|object $data, int $status = 200): self
	{
		$payload = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

		return (new self($status))
			->withHeader('Content-Type', 'application/json')
			->withBody($payload);
	}

	/**
	 * Creates a plain text response with the given content and status code.
	 */
	public static function text(string $content, int $status = 200): self
	{
		return (new self($status))
			->withHeader('Content-Type', 'text/plain; charset=utf-8')
			->withBody($content);
	}

	/**
	 * Creates an HTML response with the given content and status code.
	 */
	public static function html(string $content, int $status = 200): self
	{
		return (new self($status))
			->withHeader('Content-Type', 'text/html; charset=utf-8')
			->withBody($content);
	}

	/**
	 * Creates a redirect response (302 by default) to the given URL.
	 */
	public static function redirect(string $url, int $status = 302): self
	{
		return (new self($status))
			->withHeader('Location', $url);
	}

	/**
	 * Creates a 204 No Content response with an empty body.
	 */
	public static function noContent(): self
	{
		return new self(204);
	}

	/**
	 * Creates a 201 Created response, optionally with a Location header
	 * and JSON body.
	 *
	 * @param null|array<mixed>|object $data
	 *
	 * @throws \JsonException
	 */
	public static function created(string $location = '', array|object|null $data = null): self
	{
		$instance = new self(201);

		if ('' !== $location) {
			$instance = $instance->withHeader('Location', $location);
		}

		if (null !== $data) {
			$payload = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
			$instance = $instance
				->withHeader('Content-Type', 'application/json')
				->withBody($payload);
		}

		return $instance;
	}

	/**
	 * Returns the HTTP status code.
	 */
	public function status(): int
	{
		return $this->response->getStatusCode();
	}

	/**
	 * Sets the HTTP status code.
	 */
	public function withStatus(int $code, string $reasonPhrase = ''): self
	{
		$this->response = $this->response->withStatus($code, $reasonPhrase);

		return $this;
	}

	/**
	 * Returns a header value as a string, or $default when absent.
	 */
	public function header(string $name, string $default = ''): string
	{
		if (!$this->response->hasHeader($name)) {
			return $default;
		}

		return $this->response->getHeaderLine($name);
	}

	/**
	 * Returns true when the response has the given header.
	 */
	public function hasHeader(string $name): bool
	{
		return $this->response->hasHeader($name);
	}

	/**
	 * Sets a response header, replacing any existing value.
	 */
	public function withHeader(string $name, string $value): self
	{
		$this->response = $this->response->withHeader($name, $value);

		return $this;
	}

	/**
	 * Appends a value to an existing response header.
	 */
	public function withAddedHeader(string $name, string $value): self
	{
		$this->response = $this->response->withAddedHeader($name, $value);

		return $this;
	}

	/**
	 * Removes a response header.
	 */
	public function withoutHeader(string $name): self
	{
		$this->response = $this->response->withoutHeader($name);

		return $this;
	}

	/**
	 * Sets the response body from a string.
	 */
	public function withBody(string $content): self
	{
		$this->response = $this->response->withBody(Stream::createFromString($content));

		return $this;
	}

	/**
	 * Appends a Set-Cookie header following RFC 6265.
	 *
	 * @param array<string, mixed> $options Supports: path, domain, secure,
	 *                                      httponly, samesite, expires, maxage
	 */
	public function withCookie(string $name, string $value, array $options = []): self
	{
		$cookie = urlencode($name) . '=' . urlencode($value);

		if (isset($options['path']) && is_string($options['path'])) {
			$cookie .= '; Path=' . $options['path'];
		}

		if (isset($options['domain']) && is_string($options['domain'])) {
			$cookie .= '; Domain=' . $options['domain'];
		}

		if (isset($options['maxage']) && is_int($options['maxage'])) {
			$cookie .= '; Max-Age=' . $options['maxage'];
		}

		if (isset($options['expires']) && is_string($options['expires'])) {
			$cookie .= '; Expires=' . $options['expires'];
		}

		if (!empty($options['secure'])) {
			$cookie .= '; Secure';
		}

		if (!empty($options['httponly'])) {
			$cookie .= '; HttpOnly';
		}

		if (isset($options['samesite']) && is_string($options['samesite'])) {
			$cookie .= '; SameSite=' . $options['samesite'];
		}

		$this->response = $this->response->withAddedHeader('Set-Cookie', $cookie);

		return $this;
	}

	/**
	 * Sets the Cache-Control header.
	 */
	public function withCacheControl(string $directive): self
	{
		return $this->withHeader('Cache-Control', $directive);
	}

	/**
	 * Returns the underlying PSR-7 ResponseInterface.
	 */
	public function toPsrResponse(): ResponseInterface
	{
		return $this->response;
	}
}
