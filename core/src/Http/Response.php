<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Immutable PSR-7 HTTP response.
 *
 * Carries the status code, reason phrase, headers, and body. When no reason
 * phrase is provided, the standard RFC 7231 phrase for the given status code
 * is used automatically.
 */
final class Response extends Message implements ResponseInterface
{
	private const REASON_PHRASES = [
		100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 103 => 'Early Hints',
		200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information',
		204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status',
		208 => 'Already Reported', 226 => 'IM Used',
		300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other',
		304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect',
		400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden',
		404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone',
		411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Content Too Large',
		414 => 'URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed', 418 => 'I\'m a Teapot', 421 => 'Misdirected Request',
		422 => 'Unprocessable Content', 423 => 'Locked', 424 => 'Failed Dependency', 425 => 'Too Early',
		426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large', 451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway',
		503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected',
		510 => 'Not Extended', 511 => 'Network Authentication Required',
	];

	private readonly string $resolvedReasonPhrase;

	public function __construct(
		private readonly int $statusCode = 200,
		string $reasonPhrase = '',
		?HeaderBag $headers = null,
		?StreamInterface $body = null,
		string $protocolVersion = '1.1',
	) {
		$this->resolvedReasonPhrase = '' !== $reasonPhrase
			? $reasonPhrase
			: (self::REASON_PHRASES[$statusCode] ?? '');

		parent::__construct(
			$protocolVersion,
			$headers ?? new HeaderBag(),
			$body ?? Stream::createFromString(),
		);
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
	{
		if ($code < 100 || $code > 599) {
			throw new \InvalidArgumentException("Invalid status code: $code. Must be between 100 and 599.");
		}

		return new self($code, $reasonPhrase, $this->getHeaderBag(), $this->getBody(), $this->getProtocolVersion());
	}

	public function getReasonPhrase(): string
	{
		return $this->resolvedReasonPhrase;
	}

	protected function cloneWith(
		?string $protocolVersion = null,
		?HeaderBag $headers = null,
		?StreamInterface $body = null,
	): static {
		return new self(
			$this->statusCode,
			$this->resolvedReasonPhrase,
			$headers ?? $this->getHeaderBag(),
			$body ?? $this->getBody(),
			$protocolVersion ?? $this->getProtocolVersion(),
		);
	}
}
