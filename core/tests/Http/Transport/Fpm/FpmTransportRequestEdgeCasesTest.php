<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Transport\Fpm;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Transport\Fpm\FpmTransportRequest;
use Sakoo\Framework\Core\Tests\TestCase;

#[Group('integration')]
final class FpmTransportRequestEdgeCasesTest extends TestCase
{
	#[Test]
	public function it_uses_server_name_when_http_host_absent(): void
	{
		$transport = new FpmTransportRequest(
			server: ['SERVER_NAME' => 'api.sakoo.dev'],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame('api.sakoo.dev', $request->getUri()->getHost());
	}

	#[Test]
	public function it_treats_https_off_as_http(): void
	{
		$transport = new FpmTransportRequest(
			server: ['HTTPS' => 'off'],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame('http', $request->getUri()->getScheme());
	}

	#[Test]
	public function it_treats_empty_https_value_as_http(): void
	{
		$transport = new FpmTransportRequest(
			server: ['HTTPS' => ''],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame('http', $request->getUri()->getScheme());
	}

	#[Test]
	public function it_parses_query_from_request_uri(): void
	{
		$transport = new FpmTransportRequest(
			server: ['REQUEST_URI' => '/search?q=sakoo&page=2'],
			get: ['q' => 'sakoo', 'page' => '2'],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame('/search', $request->getUri()->getPath());
		$this->assertSame('q=sakoo&page=2', $request->getUri()->getQuery());
	}

	#[Test]
	public function it_includes_server_port_in_uri(): void
	{
		$transport = new FpmTransportRequest(
			server: ['SERVER_PORT' => 8080],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame(8080, $request->getUri()->getPort());
	}

	#[Test]
	public function it_parses_multipart_form_data_as_body(): void
	{
		$transport = new FpmTransportRequest(
			server: [
				'REQUEST_METHOD' => 'POST',
				'CONTENT_TYPE' => 'multipart/form-data; boundary=abc',
			],
			get: [],
			post: ['file_description' => 'avatar'],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame(['file_description' => 'avatar'], $request->getParsedBody());
	}

	#[Test]
	public function it_extracts_content_type_header(): void
	{
		$transport = new FpmTransportRequest(
			server: ['CONTENT_TYPE' => 'application/json'],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '{"key":"value"}',
		);

		$request = $transport->toPsrRequest();

		$this->assertTrue($request->hasHeader('Content-Type'));
		$this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
	}

	#[Test]
	public function it_extracts_content_length_header(): void
	{
		$transport = new FpmTransportRequest(
			server: ['CONTENT_LENGTH' => '42'],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertTrue($request->hasHeader('Content-Length'));
		$this->assertSame('42', $request->getHeaderLine('Content-Length'));
	}

	#[Test]
	public function it_skips_malformed_file_entry(): void
	{
		$transport = new FpmTransportRequest(
			server: [],
			get: [],
			post: [],
			cookies: [],
			files: ['bad' => 'not-an-array'],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertEmpty($request->getUploadedFiles());
	}

	#[Test]
	public function it_normalises_http_star_server_keys_to_headers(): void
	{
		$transport = new FpmTransportRequest(
			server: [
				'HTTP_X_REQUEST_ID' => 'req-abc',
				'HTTP_ACCEPT_LANGUAGE' => 'en-US',
			],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertTrue($request->hasHeader('X-REQUEST-ID'));
		$this->assertTrue($request->hasHeader('ACCEPT-LANGUAGE'));
	}

	#[Test]
	public function it_strips_protocol_prefix_for_version(): void
	{
		$transport = new FpmTransportRequest(
			server: ['SERVER_PROTOCOL' => 'HTTP/2'],
			get: [],
			post: [],
			cookies: [],
			files: [],
			body: '',
		);

		$request = $transport->toPsrRequest();

		$this->assertSame('2', $request->getProtocolVersion());
	}
}
