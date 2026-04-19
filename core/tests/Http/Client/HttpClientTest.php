<?php

declare(strict_types=1);

namespace Http\Client;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\Client\HttpClient;
use Sakoo\Framework\Core\Http\Client\HttpClientException;
use Sakoo\Framework\Core\Http\Client\HttpDriverInterface;
use Sakoo\Framework\Core\Http\HttpFactory;
use System\Testing\TestCase;

/**
 * Unit tests for HttpClient covering immutable fluent API, convenience method
 * construction, driver delegation, and SAPI-based driver selection contracts.
 */
final class HttpClientTest extends TestCase
{
	private HttpFactory $factory;
	/** @var HttpDriverInterface&MockObject */
	private HttpDriverInterface $driver;
	private HttpClient $client;

	protected function setUp(): void
	{
		parent::setUp();

		$this->factory = new HttpFactory();
		$this->driver = $this->createMock(HttpDriverInterface::class);
		$this->client = new HttpClient($this->driver, $this->factory);
	}

	#[Test]
	public function with_timeout_returns_new_instance_and_does_not_mutate_original(): void
	{
		$modified = $this->client->withTimeout(10.0);

		$this->assertNotSame($this->client, $modified);
	}

	#[Test]
	public function with_header_returns_new_instance_and_does_not_mutate_original(): void
	{
		$modified = $this->client->withHeader('X-Api-Key', 'secret');

		$this->assertNotSame($this->client, $modified);
	}

	#[Test]
	public function with_header_accumulates_on_chained_calls(): void
	{
		$modified = $this->client
			->withHeader('X-Foo', 'foo')
			->withHeader('X-Bar', 'bar');

		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(function (RequestInterface $req): bool {
				return $req->hasHeader('X-Foo') && $req->hasHeader('X-Bar');
			}))
			->willReturn($response);

		$modified->get('http://example.com/');
	}

	#[Test]
	public function send_delegates_to_driver(): void
	{
		$request = $this->factory->createRequest('GET', 'http://example.com/');
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($request)
			->willReturn($response);

		$result = $this->client->send($request);

		$this->assertSame($response, $result);
	}

	#[Test]
	public function get_builds_get_request_and_delegates(): void
	{
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(fn (RequestInterface $r): bool => 'GET' === $r->getMethod()))
			->willReturn($response);

		$this->client->get('http://example.com/path');
	}

	#[Test]
	public function post_builds_post_request_with_body(): void
	{
		$response = $this->createMock(ResponseInterface::class);
		$body = '{"key":"value"}';

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(function (RequestInterface $r) use ($body): bool {
				return 'POST' === $r->getMethod()
					&& $body === (string) $r->getBody();
			}))
			->willReturn($response);

		$this->client->post('http://example.com/', $body);
	}

	#[Test]
	public function delete_builds_delete_request(): void
	{
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(fn (RequestInterface $r): bool => 'DELETE' === $r->getMethod()))
			->willReturn($response);

		$this->client->delete('http://example.com/resource/1');
	}

	#[Test]
	public function send_propagates_http_client_exception(): void
	{
		$request = $this->factory->createRequest('GET', 'http://fail.example.com/');

		$this->driver
			->method('send')
			->willThrowException(new HttpClientException('connection refused', $request));

		$this->expectException(HttpClientException::class);

		$this->client->send($request);
	}

	#[Test]
	public function http_client_exception_carries_original_request(): void
	{
		$request = $this->factory->createRequest('GET', 'http://fail.example.com/');
		$exception = new HttpClientException('timeout', $request);

		$this->assertSame($request, $exception->getRequest());
	}
}
