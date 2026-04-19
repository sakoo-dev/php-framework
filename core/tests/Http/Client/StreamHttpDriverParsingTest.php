<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Client;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Client\StreamHttpDriver;
use Sakoo\Framework\Core\Http\HttpFactory;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Tests for StreamHttpDriver's header parsing logic, reached via Reflection
 * to avoid live network calls in unit tests.
 *
 * send() itself performs real I/O through file_get_contents so it belongs to
 * integration tests; here we verify the two pure-parsing helpers in isolation.
 */
final class StreamHttpDriverParsingTest extends TestCase
{
	private StreamHttpDriver $driver;

	protected function setUp(): void
	{
		parent::setUp();
		$this->driver = new StreamHttpDriver(new HttpFactory());
	}

	private function callPrivate(string $method, mixed ...$args): mixed
	{
		$reflection = new \ReflectionMethod(StreamHttpDriver::class, $method);

		return $reflection->invoke($this->driver, ...$args);
	}

	#[Test]
	public function parse_status_code_extracts_200(): void
	{
		$headers = ['HTTP/1.1 200 OK', 'Content-Type: text/html'];

		/** @var int $code */
		$code = $this->callPrivate('parseStatusCode', $headers);

		$this->assertSame(200, $code);
	}

	#[Test]
	public function parse_status_code_extracts_404(): void
	{
		$headers = ['HTTP/1.1 404 Not Found'];

		/** @var int $code */
		$code = $this->callPrivate('parseStatusCode', $headers);

		$this->assertSame(404, $code);
	}

	#[Test]
	public function parse_status_code_extracts_201_created(): void
	{
		$headers = ['HTTP/2 201 Created'];

		/** @var int $code */
		$code = $this->callPrivate('parseStatusCode', $headers);

		$this->assertSame(201, $code);
	}

	#[Test]
	public function parse_status_code_defaults_to_200_on_empty_headers(): void
	{
		/** @var int $code */
		$code = $this->callPrivate('parseStatusCode', []);

		$this->assertSame(200, $code);
	}

	#[Test]
	public function parse_status_code_defaults_to_200_on_malformed_status_line(): void
	{
		$headers = ['garbage line without status'];

		/** @var int $code */
		$code = $this->callPrivate('parseStatusCode', $headers);

		$this->assertSame(200, $code);
	}

	#[Test]
	public function parse_headers_returns_name_value_map(): void
	{
		$headers = [
			'HTTP/1.1 200 OK',
			'Content-Type: application/json',
			'X-Request-Id: abc123',
		];

		/** @var array<string, string> $parsed */
		$parsed = $this->callPrivate('parseHeaders', $headers);

		$this->assertSame('application/json', $parsed['Content-Type']);
		$this->assertSame('abc123', $parsed['X-Request-Id']);
	}

	#[Test]
	public function parse_headers_skips_status_line(): void
	{
		$headers = ['HTTP/1.1 200 OK', 'Content-Type: text/plain'];

		/** @var array<string, string> $parsed */
		$parsed = $this->callPrivate('parseHeaders', $headers);

		$this->assertArrayNotHasKey('HTTP/1.1 200 OK', $parsed);
		$this->assertCount(1, $parsed);
	}

	#[Test]
	public function parse_headers_skips_lines_without_colon(): void
	{
		$headers = [
			'HTTP/1.1 200 OK',
			'no colon here',
			'Content-Type: text/html',
		];

		/** @var array<string, string> $parsed */
		$parsed = $this->callPrivate('parseHeaders', $headers);

		$this->assertCount(1, $parsed);
		$this->assertArrayHasKey('Content-Type', $parsed);
	}

	#[Test]
	public function parse_headers_trims_whitespace_from_name_and_value(): void
	{
		$headers = [
			'HTTP/1.1 200 OK',
			'  X-Trimmed  :  value with spaces  ',
		];

		/** @var array<string, string> $parsed */
		$parsed = $this->callPrivate('parseHeaders', $headers);

		$this->assertArrayHasKey('X-Trimmed', $parsed);
		$this->assertSame('value with spaces', $parsed['X-Trimmed']);
	}

	#[Test]
	public function parse_headers_handles_value_with_colon(): void
	{
		$headers = [
			'HTTP/1.1 200 OK',
			'Authorization: Bearer token:with:colons',
		];

		/** @var array<string, string> $parsed */
		$parsed = $this->callPrivate('parseHeaders', $headers);

		$this->assertSame('Bearer token:with:colons', $parsed['Authorization']);
	}

	#[Test]
	public function parse_headers_returns_empty_array_when_only_status_line(): void
	{
		$headers = ['HTTP/1.1 204 No Content'];

		/** @var array<string, string> $parsed */
		$parsed = $this->callPrivate('parseHeaders', $headers);

		$this->assertSame([], $parsed);
	}
}
