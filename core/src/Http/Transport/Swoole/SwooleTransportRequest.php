<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Transport\Swoole;

use Psr\Http\Message\ServerRequestInterface;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Transport\TransportRequest;
use Sakoo\Framework\Core\Http\UploadedFile;
use Sakoo\Framework\Core\Http\Uri;
use Swoole\Http\Request as SwooleRequest;

/**
 * Swoole transport request adapter.
 *
 * Converts a Swoole\Http\Request into a PSR-7 ServerRequest.
 */
final class SwooleTransportRequest implements TransportRequest
{
	public function __construct(
		private readonly SwooleRequest $swooleRequest,
	) {}

	public function toPsrRequest(): ServerRequestInterface
	{
		/** @var array<string, mixed> $server */
		$server = $this->swooleRequest->server ?? [];
		/** @var array<string, string> $headerArray */
		$headerArray = $this->swooleRequest->header ?? [];

		$method = mb_strtoupper($this->arrString($server, 'request_method', 'GET'));
		$uri = $this->buildUri($server, $headerArray);
		$headers = HeaderBag::fromArray($headerArray);
		$body = Stream::createFromString((string) ($this->swooleRequest->rawContent() ?: ''));
		$protocol = str_replace('HTTP/', '', $this->arrString($server, 'server_protocol', 'HTTP/1.1'));

		/** @var array<string, string> $cookies */
		$cookies = $this->swooleRequest->cookie ?? [];
		/** @var array<string, mixed> $queryParams */
		$queryParams = $this->swooleRequest->get ?? [];

		$uploadedFiles = $this->normalizeFiles();

		$parsedBody = null;
		$contentType = mb_strtolower($headerArray['content-type'] ?? '');

		if ('POST' === $method && (str_contains($contentType, 'application/x-www-form-urlencoded') || str_contains($contentType, 'multipart/form-data'))) {
			$post = $this->swooleRequest->post;
			$parsedBody = is_array($post) ? $post : [];
		}

		return new ServerRequest(
			$method,
			$uri,
			$headers,
			$body,
			$protocol,
			$server,
			$cookies,
			$queryParams,
			$uploadedFiles,
			$parsedBody,
		);
	}

	/**
	 * @param array<string, mixed>  $server
	 * @param array<string, string> $headerArray
	 */
	private function buildUri(array $server, array $headerArray): Uri
	{
		$port = isset($server['server_port']) && is_numeric($server['server_port']) ? (int) $server['server_port'] : null;
		$scheme = (443 === $port) ? 'https' : 'http';
		$host = $headerArray['host'] ?? 'localhost';

		return new Uri(
			$scheme,
			'',
			$host,
			$port,
			$this->arrString($server, 'request_uri', '/'),
			$this->arrString($server, 'query_string'),
		);
	}

	/**
	 * @return array<string, UploadedFile>
	 */
	private function normalizeFiles(): array
	{
		/** @var array<string, mixed> $files */
		$files = $this->swooleRequest->files ?? [];
		$result = [];

		foreach ($files as $name => $file) {
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

	/**
	 * @param array<string, mixed> $arr
	 */
	private function arrString(array $arr, string $key, string $default = ''): string
	{
		$value = $arr[$key] ?? $default;

		if (is_string($value)) {
			return $value;
		}

		return is_scalar($value) ? (string) $value : $default;
	}
}
