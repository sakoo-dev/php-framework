<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Transport\Fpm;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Transport\Fpm\FpmTransportRequest;
use Sakoo\Framework\Core\Tests\TestCase;

#[Group('integration')]
final class FpmTransportRequestTest extends TestCase
{
	#[Test]
	public function it_builds_psr_request_from_server_arrays(): void
	{
		$transport = new FpmTransportRequest(
			server: [
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => '/api/users?page=2',
				'SERVER_PROTOCOL' => 'HTTP/1.1',
				'HTTP_HOST' => 'example.com',
				'SERVER_PORT' => 443,
				'HTTPS' => 'on',
				'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
				'CONTENT_LENGTH' => '11',
				'HTTP_ACCEPT' => 'application/json',
			],
			get: ['page' => '2'],
			post: ['name' => 'Pouya'],
			cookies: ['session' => 'abc'],
			files: [],
			body: 'name=Pouya',
		);

		$request = $transport->toPsrRequest();

		$this->assertInstanceOf(ServerRequest::class, $request);
		$this->assertSame('POST', $request->getMethod());
		$this->assertSame('https', $request->getUri()->getScheme());
		$this->assertSame('example.com', $request->getUri()->getHost());
		$this->assertSame('/api/users', $request->getUri()->getPath());
		$this->assertSame('page=2', $request->getUri()->getQuery());
		$this->assertSame('1.1', $request->getProtocolVersion());
		$this->assertSame(['page' => '2'], $request->getQueryParams());
		$this->assertSame(['session' => 'abc'], $request->getCookieParams());
		$this->assertSame(['name' => 'Pouya'], $request->getParsedBody());
		$this->assertSame('name=Pouya', (string) $request->getBody());
		$this->assertTrue($request->hasHeader('Accept'));
		$this->assertTrue($request->hasHeader('Content-Type'));
		$this->assertTrue($request->hasHeader('Content-Length'));
	}

	#[Test]
	public function it_defaults_to_get_and_http(): void
	{
		$transport = new FpmTransportRequest(
			server: [],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame('GET', $request->getMethod());
		$this->assertSame('http', $request->getUri()->getScheme());
		$this->assertSame('localhost', $request->getUri()->getHost());
	}

	#[Test]
	public function it_does_not_parse_body_for_get_requests(): void
	{
		$transport = new FpmTransportRequest(
			server: [
				'REQUEST_METHOD' => 'GET',
				'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
			],
			get: [],
			post: ['data' => 'value'],
			cookies: [],
			files: [],
			body: 'data=value',
		);

		$request = $transport->toPsrRequest();

		$this->assertNull($request->getParsedBody());
	}
}
