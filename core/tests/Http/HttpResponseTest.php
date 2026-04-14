<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Tests\TestCase;

final class HttpResponseTest extends TestCase
{
	#[Test]
	public function json_creates_response_with_correct_content_type(): void
	{
		$response = HttpResponse::json(['key' => 'value']);
		$psr = $response->toPsrResponse();

		$this->assertSame(200, $psr->getStatusCode());
		$this->assertSame('application/json', $psr->getHeaderLine('Content-Type'));
		$this->assertSame('{"key":"value"}', (string) $psr->getBody());
	}

	#[Test]
	public function json_with_custom_status(): void
	{
		$response = HttpResponse::json(['error' => 'not found'], 404);

		$this->assertSame(404, $response->toPsrResponse()->getStatusCode());
	}

	#[Test]
	public function text_creates_plain_text_response(): void
	{
		$response = HttpResponse::text('hello world');
		$psr = $response->toPsrResponse();

		$this->assertSame(200, $psr->getStatusCode());
		$this->assertSame('text/plain; charset=utf-8', $psr->getHeaderLine('Content-Type'));
		$this->assertSame('hello world', (string) $psr->getBody());
	}

	#[Test]
	public function html_creates_html_response(): void
	{
		$response = HttpResponse::html('<h1>Hello</h1>');
		$psr = $response->toPsrResponse();

		$this->assertSame(200, $psr->getStatusCode());
		$this->assertSame('text/html; charset=utf-8', $psr->getHeaderLine('Content-Type'));
		$this->assertSame('<h1>Hello</h1>', (string) $psr->getBody());
	}

	#[Test]
	public function redirect_creates_302_with_location(): void
	{
		$response = HttpResponse::redirect('/dashboard');
		$psr = $response->toPsrResponse();

		$this->assertSame(302, $psr->getStatusCode());
		$this->assertSame('/dashboard', $psr->getHeaderLine('Location'));
	}

	#[Test]
	public function redirect_with_custom_status(): void
	{
		$response = HttpResponse::redirect('/permanent', 301);

		$this->assertSame(301, $response->toPsrResponse()->getStatusCode());
	}

	#[Test]
	public function no_content_creates_204(): void
	{
		$response = HttpResponse::noContent();
		$psr = $response->toPsrResponse();

		$this->assertSame(204, $psr->getStatusCode());
		$this->assertSame('', (string) $psr->getBody());
	}

	#[Test]
	public function created_with_location_and_data(): void
	{
		$response = HttpResponse::created('/users/42', ['id' => 42]);
		$psr = $response->toPsrResponse();

		$this->assertSame(201, $psr->getStatusCode());
		$this->assertSame('/users/42', $psr->getHeaderLine('Location'));
		$this->assertSame('application/json', $psr->getHeaderLine('Content-Type'));
		$this->assertSame('{"id":42}', (string) $psr->getBody());
	}

	#[Test]
	public function created_without_data(): void
	{
		$response = HttpResponse::created('/users/42');
		$psr = $response->toPsrResponse();

		$this->assertSame(201, $psr->getStatusCode());
		$this->assertSame('/users/42', $psr->getHeaderLine('Location'));
		$this->assertSame('', (string) $psr->getBody());
	}

	#[Test]
	public function with_status_changes_status(): void
	{
		$response = (new HttpResponse())->withStatus(418, "I'm a Teapot");
		$psr = $response->toPsrResponse();

		$this->assertSame(418, $psr->getStatusCode());
		$this->assertSame("I'm a Teapot", $psr->getReasonPhrase());
	}

	#[Test]
	public function with_header_sets_header(): void
	{
		$response = (new HttpResponse())->withHeader('X-Custom', 'value');

		$this->assertSame('value', $response->toPsrResponse()->getHeaderLine('X-Custom'));
	}

	#[Test]
	public function with_added_header_appends(): void
	{
		$response = (new HttpResponse())
			->withHeader('X-Multi', 'first')
			->withAddedHeader('X-Multi', 'second');

		$this->assertSame('first, second', $response->toPsrResponse()->getHeaderLine('X-Multi'));
	}

	#[Test]
	public function without_header_removes(): void
	{
		$response = (new HttpResponse())
			->withHeader('X-Remove', 'val')
			->withoutHeader('X-Remove');

		$this->assertFalse($response->toPsrResponse()->hasHeader('X-Remove'));
	}

	#[Test]
	public function with_body_sets_body(): void
	{
		$response = (new HttpResponse())->withBody('custom body');

		$this->assertSame('custom body', (string) $response->toPsrResponse()->getBody());
	}

	#[Test]
	public function with_cookie_adds_set_cookie_header(): void
	{
		$response = (new HttpResponse())->withCookie('session', 'abc123', [
			'path' => '/',
			'domain' => '.example.com',
			'secure' => true,
			'httponly' => true,
			'samesite' => 'Lax',
			'maxage' => 3600,
		]);

		$cookie = $response->toPsrResponse()->getHeaderLine('Set-Cookie');

		$this->assertStringContainsString('session=abc123', $cookie);
		$this->assertStringContainsString('Path=/', $cookie);
		$this->assertStringContainsString('Domain=.example.com', $cookie);
		$this->assertStringContainsString('Secure', $cookie);
		$this->assertStringContainsString('HttpOnly', $cookie);
		$this->assertStringContainsString('SameSite=Lax', $cookie);
		$this->assertStringContainsString('Max-Age=3600', $cookie);
	}

	#[Test]
	public function with_cache_control(): void
	{
		$response = (new HttpResponse())->withCacheControl('no-store, max-age=0');

		$this->assertSame('no-store, max-age=0', $response->toPsrResponse()->getHeaderLine('Cache-Control'));
	}

	#[Test]
	public function fluent_chaining(): void
	{
		$response = (new HttpResponse())
			->withStatus(201)
			->withHeader('Content-Type', 'application/json')
			->withHeader('X-Request-Id', 'abc')
			->withBody('{"created":true}');

		$psr = $response->toPsrResponse();

		$this->assertSame(201, $psr->getStatusCode());
		$this->assertSame('application/json', $psr->getHeaderLine('Content-Type'));
		$this->assertSame('abc', $psr->getHeaderLine('X-Request-Id'));
		$this->assertSame('{"created":true}', (string) $psr->getBody());
	}
}
