<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\Request;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\TestCase;

final class RequestTest extends TestCase
{
	private function makeRequest(
		string $method = 'GET',
		string $uri = 'http://example.com/path?q=1',
	): Request {
		return new Request(
			$method,
			Uri::fromString($uri),
			new HeaderBag(),
			Stream::createFromString(),
		);
	}

	#[Test]
	public function it_returns_method(): void
	{
		$request = $this->makeRequest('POST');

		$this->assertSame('POST', $request->getMethod());
	}

	#[Test]
	public function it_returns_uri(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com/test');

		$this->assertSame('/test', $request->getUri()->getPath());
	}

	#[Test]
	public function it_returns_request_target_from_uri(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com/path?q=1');

		$this->assertSame('/path?q=1', $request->getRequestTarget());
	}

	#[Test]
	public function it_returns_slash_when_path_is_empty(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com');

		$this->assertSame('/', $request->getRequestTarget());
	}

	#[Test]
	public function with_request_target_returns_new_instance(): void
	{
		$request = $this->makeRequest();
		$new = $request->withRequestTarget('/custom');

		$this->assertSame('/custom', $new->getRequestTarget());
		$this->assertNotSame($request, $new);
	}

	#[Test]
	public function with_method_returns_new_instance(): void
	{
		$request = $this->makeRequest('GET');
		$new = $request->withMethod('POST');

		$this->assertSame('GET', $request->getMethod());
		$this->assertSame('POST', $new->getMethod());
	}

	#[Test]
	public function with_uri_returns_new_instance(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com/old');
		$newUri = Uri::fromString('http://other.com/new');
		$new = $request->withUri($newUri);

		$this->assertSame('/old', $request->getUri()->getPath());
		$this->assertSame('/new', $new->getUri()->getPath());
	}

	#[Test]
	public function with_uri_updates_host_header(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com');
		$new = $request->withUri(Uri::fromString('http://other.com'));

		$this->assertSame('other.com', $new->getHeaderLine('Host'));
	}

	#[Test]
	public function with_uri_preserves_host_when_flag_set(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com');
		$new = $request->withUri(Uri::fromString('http://other.com'), true);

		$this->assertSame('example.com', $new->getHeaderLine('Host'));
	}

	#[Test]
	public function host_header_is_set_from_uri_on_construction(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com');

		$this->assertTrue($request->hasHeader('Host'));
		$this->assertSame('example.com', $request->getHeaderLine('Host'));
	}

	#[Test]
	public function host_header_includes_non_standard_port(): void
	{
		$request = $this->makeRequest(uri: 'http://example.com:8080');

		$this->assertSame('example.com:8080', $request->getHeaderLine('Host'));
	}

	#[Test]
	public function with_body_returns_new_instance(): void
	{
		$request = $this->makeRequest();
		$body = Stream::createFromString('new body');
		$new = $request->withBody($body);

		$this->assertSame('new body', (string) $new->getBody());
		$this->assertNotSame($request, $new);
	}

	#[Test]
	public function with_protocol_version_returns_new_instance(): void
	{
		$request = $this->makeRequest();
		$new = $request->withProtocolVersion('2.0');

		$this->assertSame('1.1', $request->getProtocolVersion());
		$this->assertSame('2.0', $new->getProtocolVersion());
	}
}
