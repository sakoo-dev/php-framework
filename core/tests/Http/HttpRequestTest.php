<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\UploadedFile;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\TestCase;

final class HttpRequestTest extends TestCase
{
	private function make(
		string $method = 'GET',
		string $uri = 'https://example.com/users?page=2',
		string $body = '',
		// @var array<string, string> $headers
		array $headers = [],
		// @var array<string, string> $cookies
		array $cookies = [],
		// @var array<string, mixed> $serverParams
		array $serverParams = [],
		// @var array<string, mixed> $queryParams
		array $queryParams = [],
		// @var null|array<mixed> $parsedBody
		?array $parsedBody = null,
		// @var array<string, mixed> $attributes
		array $attributes = [],
	): HttpRequest {
		$parsedUri = Uri::fromString($uri);
		$headerBag = HeaderBag::fromArray($headers);

		if ([] === $queryParams) {
			parse_str($parsedUri->getQuery(), $queryParams);
		}

		$psrRequest = new ServerRequest(
			$method,
			$parsedUri,
			$headerBag,
			Stream::createFromString($body),
			'1.1',
			$serverParams,
			$cookies,
			$queryParams,
			[],
			$parsedBody,
			$attributes,
		);

		return new HttpRequest($psrRequest);
	}

	#[Test]
	public function it_returns_method(): void
	{
		$request = $this->make('POST');

		$this->assertSame('POST', $request->method());
	}

	#[Test]
	public function it_checks_method(): void
	{
		$request = $this->make('POST');

		$this->assertTrue($request->isMethod('post'));
		$this->assertTrue($request->isMethod('POST'));
		$this->assertFalse($request->isMethod('GET'));
	}

	#[Test]
	public function it_returns_path(): void
	{
		$request = $this->make(uri: 'http://example.com/users/42');

		$this->assertSame('/users/42', $request->path());
	}

	#[Test]
	public function it_returns_full_url(): void
	{
		$request = $this->make(uri: 'https://example.com/path?q=1');

		$this->assertSame('https://example.com/path?q=1', $request->url());
	}

	#[Test]
	public function it_returns_scheme_host_port(): void
	{
		$request = $this->make(uri: 'https://example.com:8080/path');

		$this->assertSame('https', $request->scheme());
		$this->assertSame('example.com', $request->host());
		$this->assertSame(8080, $request->port());
		$this->assertTrue($request->isSecure());
	}

	#[Test]
	public function it_detects_http_as_not_secure(): void
	{
		$request = $this->make(uri: 'http://example.com');

		$this->assertFalse($request->isSecure());
	}

	#[Test]
	public function it_returns_query_param(): void
	{
		$request = $this->make(uri: 'http://example.com?foo=bar&baz=qux');

		$this->assertSame('bar', $request->query('foo'));
		$this->assertSame('qux', $request->query('baz'));
		$this->assertNull($request->query('missing'));
		$this->assertSame('fallback', $request->query('missing', 'fallback'));
	}

	#[Test]
	public function it_returns_all_query_params(): void
	{
		$request = $this->make(queryParams: ['a' => '1', 'b' => '2']);

		$this->assertSame(['a' => '1', 'b' => '2'], $request->queryAll());
	}

	#[Test]
	public function it_returns_input_from_parsed_body(): void
	{
		$request = $this->make(parsedBody: ['name' => 'Pouya', 'role' => 'engineer']);

		$this->assertSame('Pouya', $request->input('name'));
		$this->assertSame('engineer', $request->input('role'));
	}

	#[Test]
	public function it_falls_back_to_query_when_not_in_body(): void
	{
		$request = $this->make(
			queryParams: ['page' => '3'],
			parsedBody: ['name' => 'Pouya'],
		);

		$this->assertSame('3', $request->input('page'));
	}

	#[Test]
	public function it_returns_default_when_input_missing(): void
	{
		$request = $this->make();

		$this->assertSame('default', $request->input('missing', 'default'));
	}

	#[Test]
	public function it_returns_all_merged_input(): void
	{
		$request = $this->make(
			queryParams: ['page' => '1'],
			parsedBody: ['name' => 'Sakoo'],
		);

		$all = $request->all();

		$this->assertSame('Sakoo', $all['name']);
		$this->assertSame('1', $all['page']);
	}

	#[Test]
	public function it_checks_has_input(): void
	{
		$request = $this->make(
			queryParams: ['q' => 'test'],
			parsedBody: ['name' => 'val'],
		);

		$this->assertTrue($request->has('name'));
		$this->assertTrue($request->has('q'));
		$this->assertFalse($request->has('missing'));
	}

	#[Test]
	public function it_returns_header(): void
	{
		$request = $this->make(headers: ['Accept' => 'application/json']);

		$this->assertSame('application/json', $request->header('Accept'));
		$this->assertSame('', $request->header('Missing'));
		$this->assertSame('fallback', $request->header('Missing', 'fallback'));
	}

	#[Test]
	public function it_returns_header_array(): void
	{
		$request = $this->make(headers: ['Accept' => 'text/html']);

		$this->assertSame(['text/html'], $request->headers('Accept'));
	}

	#[Test]
	public function it_checks_has_header(): void
	{
		$request = $this->make(headers: ['X-Custom' => 'yes']);

		$this->assertTrue($request->hasHeader('X-Custom'));
		$this->assertFalse($request->hasHeader('X-Missing'));
	}

	#[Test]
	public function it_extracts_bearer_token(): void
	{
		$request = $this->make(headers: ['Authorization' => 'Bearer abc123token']);

		$this->assertSame('abc123token', $request->bearerToken());
	}

	#[Test]
	public function it_returns_null_when_no_bearer_token(): void
	{
		$request = $this->make(headers: ['Authorization' => 'Basic dXNlcjpwYXNz']);

		$this->assertNull($request->bearerToken());
	}

	#[Test]
	public function it_returns_null_when_no_auth_header(): void
	{
		$request = $this->make();

		$this->assertNull($request->bearerToken());
	}

	#[Test]
	public function it_returns_cookie(): void
	{
		$request = $this->make(cookies: ['session' => 'abc', 'theme' => 'dark']);

		$this->assertSame('abc', $request->cookie('session'));
		$this->assertSame('dark', $request->cookie('theme'));
		$this->assertSame('', $request->cookie('missing'));
		$this->assertSame('light', $request->cookie('missing', 'light'));
	}

	#[Test]
	public function it_returns_all_cookies(): void
	{
		$request = $this->make(cookies: ['a' => '1', 'b' => '2']);

		$this->assertSame(['a' => '1', 'b' => '2'], $request->cookies());
	}

	#[Test]
	public function it_returns_route_param(): void
	{
		$request = $this->make(attributes: ['id' => '42', 'slug' => 'hello']);

		$this->assertSame('42', $request->routeParam('id'));
		$this->assertSame('hello', $request->routeParam('slug'));
		$this->assertNull($request->routeParam('missing'));
		$this->assertSame('def', $request->routeParam('missing', 'def'));
	}

	#[Test]
	public function it_returns_all_route_params(): void
	{
		$request = $this->make(attributes: ['id' => '42']);

		$this->assertSame(['id' => '42'], $request->routeParams());
	}

	#[Test]
	public function it_returns_raw_body(): void
	{
		$request = $this->make(body: '{"key":"value"}');

		$this->assertSame('{"key":"value"}', $request->body());
	}

	#[Test]
	public function it_decodes_json_body(): void
	{
		$request = $this->make(body: '{"name":"Sakoo","version":1}');

		$json = $request->json();

		$this->assertSame('Sakoo', $json['name']);
		$this->assertSame(1, $json['version']);
	}

	#[Test]
	public function it_returns_content_type(): void
	{
		$request = $this->make(headers: ['Content-Type' => 'application/json']);

		$this->assertSame('application/json', $request->contentType());
		$this->assertTrue($request->isJson());
	}

	#[Test]
	public function it_detects_non_json(): void
	{
		$request = $this->make(headers: ['Content-Type' => 'text/html']);

		$this->assertFalse($request->isJson());
	}

	#[Test]
	public function it_returns_ip_from_remote_addr(): void
	{
		$request = $this->make(serverParams: ['REMOTE_ADDR' => '192.168.1.1']);

		$this->assertSame('192.168.1.1', $request->ip());
	}

	#[Test]
	public function it_returns_ip_from_forwarded_header(): void
	{
		$request = $this->make(headers: ['X-Forwarded-For' => '10.0.0.1, 10.0.0.2']);

		$this->assertSame('10.0.0.1', $request->ip());
	}

	#[Test]
	public function it_returns_user_agent(): void
	{
		$request = $this->make(headers: ['User-Agent' => 'Sakoo/1.0']);

		$this->assertSame('Sakoo/1.0', $request->userAgent());
	}

	#[Test]
	public function it_returns_server_param(): void
	{
		$request = $this->make(serverParams: ['SERVER_NAME' => 'sakoo.dev']);

		$this->assertSame('sakoo.dev', $request->server('SERVER_NAME'));
		$this->assertNull($request->server('MISSING'));
		$this->assertSame('def', $request->server('MISSING', 'def'));
	}

	#[Test]
	public function it_exposes_psr_request(): void
	{
		$request = $this->make();

		$this->assertInstanceOf(ServerRequestInterface::class, $request->psrRequest());
	}

	#[Test]
	public function it_detects_uploaded_file(): void
	{
		$file = new UploadedFile(Stream::createFromString('data'), 4, UPLOAD_ERR_OK, 'test.txt');

		$psrRequest = new ServerRequest(
			'POST',
			Uri::fromString('http://localhost'),
			new HeaderBag(),
			Stream::createFromString(),
			'1.1',
			uploadedFiles: ['avatar' => $file],
		);

		$request = new HttpRequest($psrRequest);

		$this->assertTrue($request->hasFile('avatar'));
		$this->assertFalse($request->hasFile('missing'));
		$this->assertSame($file, $request->file('avatar'));
		$this->assertNull($request->file('missing'));
		$this->assertCount(1, $request->files());
	}
}
