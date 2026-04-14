<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\UploadedFile;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\TestCase;

final class ServerRequestTest extends TestCase
{
	private function makeServerRequest(): ServerRequest
	{
		return new ServerRequest(
			'GET',
			Uri::fromString('http://example.com/path?q=1'),
			new HeaderBag(),
			Stream::createFromString(),
			'1.1',
			['SERVER_NAME' => 'example.com'],
			['session' => 'abc123'],
			['q' => '1'],
		);
	}

	#[Test]
	public function it_returns_server_params(): void
	{
		$request = $this->makeServerRequest();

		$this->assertSame(['SERVER_NAME' => 'example.com'], $request->getServerParams());
	}

	#[Test]
	public function it_returns_cookie_params(): void
	{
		$request = $this->makeServerRequest();

		$this->assertSame(['session' => 'abc123'], $request->getCookieParams());
	}

	#[Test]
	public function with_cookie_params_returns_new_instance(): void
	{
		$request = $this->makeServerRequest();
		$new = $request->withCookieParams(['token' => 'xyz']);

		$this->assertSame(['session' => 'abc123'], $request->getCookieParams());
		$this->assertSame(['token' => 'xyz'], $new->getCookieParams());
	}

	#[Test]
	public function it_returns_query_params(): void
	{
		$request = $this->makeServerRequest();

		$this->assertSame(['q' => '1'], $request->getQueryParams());
	}

	#[Test]
	public function with_query_params_returns_new_instance(): void
	{
		$request = $this->makeServerRequest();
		$new = $request->withQueryParams(['page' => '2']);

		$this->assertSame(['q' => '1'], $request->getQueryParams());
		$this->assertSame(['page' => '2'], $new->getQueryParams());
	}

	#[Test]
	public function it_returns_empty_uploaded_files_by_default(): void
	{
		$request = $this->makeServerRequest();

		$this->assertSame([], $request->getUploadedFiles());
	}

	#[Test]
	public function with_uploaded_files_returns_new_instance(): void
	{
		$request = $this->makeServerRequest();
		$file = new UploadedFile(Stream::createFromString('content'));
		$new = $request->withUploadedFiles(['avatar' => $file]);

		$this->assertSame([], $request->getUploadedFiles());
		$this->assertCount(1, $new->getUploadedFiles());
	}

	#[Test]
	public function it_returns_null_parsed_body_by_default(): void
	{
		$request = $this->makeServerRequest();

		$this->assertNull($request->getParsedBody());
	}

	#[Test]
	public function with_parsed_body_returns_new_instance(): void
	{
		$request = $this->makeServerRequest();
		$new = $request->withParsedBody(['name' => 'John']);

		$this->assertNull($request->getParsedBody());
		$this->assertSame(['name' => 'John'], $new->getParsedBody());
	}

	#[Test]
	public function with_parsed_body_accepts_null(): void
	{
		$request = $this->makeServerRequest()->withParsedBody(['data' => 'val']);
		$new = $request->withParsedBody(null);

		$this->assertNull($new->getParsedBody());
	}

	#[Test]
	public function it_returns_empty_attributes_by_default(): void
	{
		$request = $this->makeServerRequest();

		$this->assertSame([], $request->getAttributes());
	}

	#[Test]
	public function with_attribute_returns_new_instance(): void
	{
		$request = $this->makeServerRequest();
		$new = $request->withAttribute('userId', 42);

		$this->assertSame([], $request->getAttributes());
		$this->assertSame(42, $new->getAttribute('userId'));
	}

	#[Test]
	public function get_attribute_returns_default_when_missing(): void
	{
		$request = $this->makeServerRequest();

		$this->assertSame('default', $request->getAttribute('missing', 'default'));
		$this->assertNull($request->getAttribute('missing'));
	}

	#[Test]
	public function without_attribute_returns_new_instance(): void
	{
		$request = $this->makeServerRequest()->withAttribute('key', 'val');
		$new = $request->withoutAttribute('key');

		$this->assertSame('val', $request->getAttribute('key'));
		$this->assertNull($new->getAttribute('key'));
	}

	#[Test]
	public function server_request_preserves_parent_immutability(): void
	{
		$request = $this->makeServerRequest();
		$new = $request->withHeader('X-Custom', 'value');

		$this->assertFalse($request->hasHeader('X-Custom'));
		$this->assertTrue($new->hasHeader('X-Custom'));
		$this->assertInstanceOf(ServerRequest::class, $new);
	}
}
