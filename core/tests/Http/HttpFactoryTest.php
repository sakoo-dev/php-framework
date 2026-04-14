<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HttpFactory;
use Sakoo\Framework\Core\Http\Request;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\UploadedFile;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Tests\TestCase;

final class HttpFactoryTest extends TestCase
{
	private HttpFactory $factory;

	protected function setUp(): void
	{
		parent::setUp();
		$this->factory = new HttpFactory();
	}

	#[Test]
	public function it_creates_request(): void
	{
		$request = $this->factory->createRequest('GET', 'http://example.com/path');

		$this->assertInstanceOf(Request::class, $request);
		$this->assertSame('GET', $request->getMethod());
		$this->assertSame('/path', $request->getUri()->getPath());
	}

	#[Test]
	public function it_creates_request_from_uri_object(): void
	{
		$uri = Uri::fromString('http://example.com/test');
		$request = $this->factory->createRequest('POST', $uri);

		$this->assertSame('POST', $request->getMethod());
		$this->assertSame('/test', $request->getUri()->getPath());
	}

	#[Test]
	public function it_creates_response(): void
	{
		$response = $this->factory->createResponse(201, 'Created');

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('Created', $response->getReasonPhrase());
	}

	#[Test]
	public function it_creates_response_with_defaults(): void
	{
		$response = $this->factory->createResponse();

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('OK', $response->getReasonPhrase());
	}

	#[Test]
	public function it_creates_server_request(): void
	{
		$request = $this->factory->createServerRequest('POST', 'http://example.com', ['KEY' => 'val']);

		$this->assertInstanceOf(ServerRequest::class, $request);
		$this->assertSame('POST', $request->getMethod());
		$this->assertSame(['KEY' => 'val'], $request->getServerParams());
	}

	#[Test]
	public function it_creates_stream(): void
	{
		$stream = $this->factory->createStream('hello');

		$this->assertInstanceOf(Stream::class, $stream);
		$this->assertSame('hello', (string) $stream);
	}

	#[Test]
	public function it_creates_stream_from_file(): void
	{
		$path = Path::getTempTestDir() . '/factory_stream_test.txt';
		@mkdir(dirname($path), 0777, true);
		file_put_contents($path, 'file content');

		$stream = $this->factory->createStreamFromFile($path);

		$this->assertSame('file content', (string) $stream);

		unlink($path);
	}

	#[Test]
	public function it_creates_stream_from_resource(): void
	{
		$resource = fopen('php://temp', 'r+');
		fwrite($resource, 'from resource');
		rewind($resource);

		$stream = $this->factory->createStreamFromResource($resource);

		$this->assertSame('from resource', (string) $stream);
	}

	#[Test]
	public function it_creates_uploaded_file(): void
	{
		$stream = Stream::createFromString('upload data');
		$file = $this->factory->createUploadedFile($stream, 11, UPLOAD_ERR_OK, 'doc.pdf', 'application/pdf');

		$this->assertInstanceOf(UploadedFile::class, $file);
		$this->assertSame(11, $file->getSize());
		$this->assertSame('doc.pdf', $file->getClientFilename());
		$this->assertSame('application/pdf', $file->getClientMediaType());
	}

	#[Test]
	public function it_creates_uri(): void
	{
		$uri = $this->factory->createUri('https://example.com:8080/path');

		$this->assertInstanceOf(Uri::class, $uri);
		$this->assertSame('https', $uri->getScheme());
		$this->assertSame(8080, $uri->getPort());
	}
}
