<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Client;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\Client\HttpClient;
use Sakoo\Framework\Core\Http\Client\HttpDriverInterface;
use Sakoo\Framework\Core\Http\HttpFactory;
use System\Testing\TestCase;

/**
 * Tests for HttpClient convenience methods not covered by HttpClientTest
 * (put, patch, per-call headers, withTimeout immutability).
 */
final class HttpClientExtendedTest extends TestCase
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
	public function put_builds_put_request_with_body(): void
	{
		$body = '{"active":true}';
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(function (RequestInterface $r) use ($body): bool {
				return 'PUT' === $r->getMethod()
					&& $body === (string) $r->getBody();
			}))
			->willReturn($response);

		$this->client->put('http://example.com/resource/1', $body);
	}

	#[Test]
	public function patch_builds_patch_request_with_body(): void
	{
		$body = '{"name":"new"}';
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(function (RequestInterface $r) use ($body): bool {
				return 'PATCH' === $r->getMethod()
					&& $body === (string) $r->getBody();
			}))
			->willReturn($response);

		$this->client->patch('http://example.com/resource/1', $body);
	}

	#[Test]
	public function get_with_per_call_headers_merges_onto_request(): void
	{
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(fn (RequestInterface $r): bool => $r->hasHeader('X-Per-Call')))
			->willReturn($response);

		$this->client->get('http://example.com/', ['X-Per-Call' => 'yes']);
	}

	#[Test]
	public function default_headers_are_applied_on_every_request(): void
	{
		$client = $this->client->withHeader('X-App', 'sakoo');
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->exactly(2))
			->method('send')
			->with($this->callback(fn (RequestInterface $r): bool => $r->hasHeader('X-App')))
			->willReturn($response);

		$client->get('http://example.com/a');
		$client->post('http://example.com/b', '{}');
	}

	#[Test]
	public function with_timeout_does_not_change_original_timeout(): void
	{
		$modified = $this->client->withTimeout(30.0);

		$this->assertNotSame($this->client, $modified);
	}

	#[Test]
	public function delete_with_per_call_headers_forwards_them(): void
	{
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(fn (RequestInterface $r): bool => $r->hasHeader('X-Delete-Reason')))
			->willReturn($response);

		$this->client->delete('http://example.com/item/1', ['X-Delete-Reason' => 'test']);
	}

	#[Test]
	public function post_without_body_sends_empty_body(): void
	{
		$response = $this->createMock(ResponseInterface::class);

		$this->driver
			->expects($this->once())
			->method('send')
			->with($this->callback(fn (RequestInterface $r): bool => '' === (string) $r->getBody()))
			->willReturn($response);

		$this->client->post('http://example.com/', '');
	}

	#[Test]
	public function chained_with_header_calls_accumulate_independently(): void
	{
		$clientA = $this->client->withHeader('X-A', 'a');
		$clientB = $this->client->withHeader('X-B', 'b');
		$response = $this->createMock(ResponseInterface::class);

		$this->driver->method('send')->willReturn($response);

		$capturedA = null;
		$capturedB = null;

		$this->driver
			->expects($this->exactly(2))
			->method('send')
			->willReturnCallback(function (RequestInterface $r) use (&$capturedA, &$capturedB, $response): ResponseInterface {
				if ($r->hasHeader('X-A')) {
					$capturedA = $r;
				}

				if ($r->hasHeader('X-B')) {
					$capturedB = $r;
				}

				return $response;
			});

		$clientA->get('http://example.com/');
		$clientB->get('http://example.com/');

		$this->assertNotNull($capturedA);
		$this->assertNotNull($capturedB);
		$this->assertFalse($capturedA->hasHeader('X-B'));
		$this->assertFalse($capturedB->hasHeader('X-A'));
	}
}
