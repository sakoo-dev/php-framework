<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Transport\Fpm;

use Psr\Http\Message\ServerRequestInterface;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Transport\TransportRequest;
use Sakoo\Framework\Core\Http\UploadedFile;
use Sakoo\Framework\Core\Http\Uri;

/**
 * FPM transport request adapter.
 *
 * Reads PHP superglobals and php://input to assemble a PSR-7 ServerRequest.
 * This is the only place in the framework that touches superglobals directly.
 */
final class FpmTransportRequest implements TransportRequest
{
	/**
	 * @param array<string, mixed>  $server
	 * @param array<string, string> $get
	 * @param array<string, mixed>  $post
	 * @param array<string, string> $cookies
	 * @param array<string, mixed>  $files
	 */
	public function __construct(
		private readonly array $server,
		private readonly array $get,
		private readonly array $post,
		private readonly array $cookies,
		private readonly array $files,
		private readonly string $body,
	) {}

	/**
	 * Factory that captures the current PHP superglobal state.
	 */
	public static function fromGlobals(): self
	{
		return new self(
			// @phpstan-ignore argument.type
			$_SERVER,
			// @phpstan-ignore argument.type
			$_GET,
			// @phpstan-ignore argument.type
			$_POST,
			// @phpstan-ignore argument.type
			$_COOKIE,
			// @phpstan-ignore argument.type
			$_FILES,
			(string) file_get_contents('php://input'),
		);
	}

	public function toPsrRequest(): ServerRequestInterface
	{
		$method = $this->serverString('REQUEST_METHOD', 'GET');
		$uri = $this->buildUri();
		$headers = $this->extractHeaders();
		$body = Stream::createFromString($this->body);
		$protocol = $this->extractProtocolVersion();
		$uploadedFiles = $this->normalizeFiles();

		$parsedBody = null;
		$contentType = mb_strtolower($this->serverString('CONTENT_TYPE'));

		if (
			'POST' === $method
			&& (
				str_contains($contentType, 'application/x-www-form-urlencoded')
				|| str_contains($contentType, 'multipart/form-data')
			)
		) {
			$parsedBody = $this->post;
		}

		return new ServerRequest(
			$method,
			$uri,
			$headers,
			$body,
			$protocol,
			$this->server,
			$this->cookies,
			$this->get,
			$uploadedFiles,
			$parsedBody,
		);
	}

	private function buildUri(): Uri
	{
		$https = $this->serverString('HTTPS');
		$scheme = ('' !== $https && 'off' !== $https) ? 'https' : 'http';
		$host = $this->serverString('HTTP_HOST');

		if ('' === $host) {
			$host = $this->serverString('SERVER_NAME', 'localhost');
		}

		$port = $this->serverInt('SERVER_PORT');
		$path = $this->serverString('REQUEST_URI', '/');
		$query = '';

		$questionMark = strpos($path, '?');

		if (false !== $questionMark) {
			$query = substr($path, $questionMark + 1);
			$path = substr($path, 0, $questionMark);
		}

		return new Uri($scheme, '', $host, $port, $path, $query);
	}

	private function extractHeaders(): HeaderBag
	{
		$headers = [];

		foreach ($this->server as $key => $value) {
			if (str_starts_with($key, 'HTTP_')) {
				$name = str_replace('_', '-', substr($key, 5));
				$headers[$name] = is_string($value) ? $value : (is_scalar($value) ? (string) $value : '');
			}
		}

		$contentType = $this->serverString('CONTENT_TYPE');

		if ('' !== $contentType) {
			$headers['Content-Type'] = $contentType;
		}

		$contentLength = $this->serverString('CONTENT_LENGTH');

		if ('' !== $contentLength) {
			$headers['Content-Length'] = $contentLength;
		}

		return HeaderBag::fromArray($headers);
	}

	private function extractProtocolVersion(): string
	{
		$protocol = $this->serverString('SERVER_PROTOCOL', 'HTTP/1.1');

		return str_replace('HTTP/', '', $protocol);
	}

	/**
	 * @return array<string, UploadedFile>
	 */
	private function normalizeFiles(): array
	{
		$result = [];

		foreach ($this->files as $name => $file) {
			if (!is_array($file) || !isset($file['tmp_name'])) {
				continue;
			}

			$tmpName = is_string($file['tmp_name']) ? $file['tmp_name'] : '';
			$content = ('' !== $tmpName && is_file($tmpName)) ? (string) file_get_contents($tmpName) : '';

			$result[$name] = new UploadedFile(
				Stream::createFromString($content),
				isset($file['size']) && is_numeric($file['size']) ? (int) $file['size'] : null,
				isset($file['error']) && is_numeric($file['error']) ? (int) $file['error'] : UPLOAD_ERR_OK,
				isset($file['name']) && is_string($file['name']) ? $file['name'] : null,
				isset($file['type']) && is_string($file['type']) ? $file['type'] : null,
			);
		}

		return $result;
	}

	private function serverString(string $key, string $default = ''): string
	{
		$value = $this->server[$key] ?? $default;

		if (is_string($value)) {
			return $value;
		}

		return is_scalar($value) ? (string) $value : $default;
	}

	private function serverInt(string $key): ?int
	{
		if (!isset($this->server[$key])) {
			return null;
		}

		$value = $this->server[$key];

		return is_numeric($value) ? (int) $value : null;
	}
}
