<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Tests\TestCase;

final class ResponseTest extends TestCase
{
	#[Test]
	public function it_creates_with_defaults(): void
	{
		$response = new Response();

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('OK', $response->getReasonPhrase());
		$this->assertSame('1.1', $response->getProtocolVersion());
	}

	#[Test]
	public function it_creates_with_custom_status(): void
	{
		$response = new Response(404);

		$this->assertSame(404, $response->getStatusCode());
		$this->assertSame('Not Found', $response->getReasonPhrase());
	}

	#[Test]
	public function it_uses_custom_reason_phrase(): void
	{
		$response = new Response(200, 'All Good');

		$this->assertSame('All Good', $response->getReasonPhrase());
	}

	#[Test]
	public function with_status_returns_new_instance(): void
	{
		$response = new Response(200);
		$new = $response->withStatus(404);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame(404, $new->getStatusCode());
		$this->assertSame('Not Found', $new->getReasonPhrase());
		$this->assertNotSame($response, $new);
	}

	#[Test]
	public function with_status_accepts_custom_reason(): void
	{
		$response = new Response(200);
		$new = $response->withStatus(200, 'Custom');

		$this->assertSame('Custom', $new->getReasonPhrase());
	}

	#[Test]
	public function with_status_throws_on_invalid_code(): void
	{
		$response = new Response();

		$this->expectException(\InvalidArgumentException::class);
		$response->withStatus(999);
	}

	#[Test]
	public function with_body_returns_new_instance(): void
	{
		$response = new Response();
		$body = Stream::createFromString('hello');
		$new = $response->withBody($body);

		$this->assertSame('hello', (string) $new->getBody());
		$this->assertNotSame($response, $new);
	}

	#[Test]
	public function with_header_returns_new_instance(): void
	{
		$response = new Response();
		$new = $response->withHeader('X-Test', 'value');

		$this->assertFalse($response->hasHeader('X-Test'));
		$this->assertTrue($new->hasHeader('X-Test'));
		$this->assertSame(['value'], $new->getHeader('X-Test'));
	}

	#[Test]
	public function with_added_header_appends(): void
	{
		$response = (new Response())->withHeader('Accept', 'text/html');
		$new = $response->withAddedHeader('Accept', 'application/json');

		$this->assertSame(['text/html', 'application/json'], $new->getHeader('Accept'));
	}

	#[Test]
	public function without_header_removes(): void
	{
		$response = (new Response())->withHeader('X-Remove', 'val');
		$new = $response->withoutHeader('X-Remove');

		$this->assertFalse($new->hasHeader('X-Remove'));
	}

	#[Test]
	public function with_protocol_version_returns_new_instance(): void
	{
		$response = new Response();
		$new = $response->withProtocolVersion('2.0');

		$this->assertSame('1.1', $response->getProtocolVersion());
		$this->assertSame('2.0', $new->getProtocolVersion());
	}

	#[Test]
	public function header_lookup_is_case_insensitive(): void
	{
		$response = (new Response())->withHeader('Content-Type', 'text/html');

		$this->assertTrue($response->hasHeader('content-type'));
		$this->assertSame(['text/html'], $response->getHeader('CONTENT-TYPE'));
		$this->assertSame('text/html', $response->getHeaderLine('content-type'));
	}
}
