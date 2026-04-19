<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Additional HttpResponse tests covering factory methods and branches not
 * exercised by HttpResponseTest (fromPsr, expires cookie option, status(),
 * header() accessor, and hasHeader() helpers on the builder).
 */
final class HttpResponseExtendedTest extends TestCase
{
	#[Test]
	public function from_psr_wraps_existing_psr_response(): void
	{
		$psr = new Response(
			202,
			'Accepted',
			HeaderBag::fromArray(['X-Original' => 'yes']),
			Stream::createFromString('body'),
		);

		$httpResponse = HttpResponse::fromPsr($psr);

		$this->assertSame(202, $httpResponse->status());
		$this->assertTrue($httpResponse->hasHeader('X-Original'));
		$this->assertSame('yes', $httpResponse->header('X-Original'));
	}

	#[Test]
	public function from_psr_then_mutate_does_not_change_original(): void
	{
		$psr = new Response(200);
		$httpResponse = HttpResponse::fromPsr($psr);

		$httpResponse->withHeader('X-Added', 'value');

		$this->assertFalse($psr->hasHeader('X-Added'));
	}

	#[Test]
	public function status_returns_current_status_code(): void
	{
		$response = new HttpResponse(418);

		$this->assertSame(418, $response->status());
	}

	#[Test]
	public function header_returns_default_when_absent(): void
	{
		$response = new HttpResponse(200);

		$this->assertSame('', $response->header('X-Missing'));
		$this->assertSame('fallback', $response->header('X-Missing', 'fallback'));
	}

	#[Test]
	public function has_header_returns_false_when_absent(): void
	{
		$response = new HttpResponse(200);

		$this->assertFalse($response->hasHeader('X-Absent'));
	}

	#[Test]
	public function has_header_returns_true_after_with_header(): void
	{
		$response = (new HttpResponse())->withHeader('X-Present', 'yes');

		$this->assertTrue($response->hasHeader('X-Present'));
	}

	#[Test]
	public function with_cookie_includes_expires_attribute(): void
	{
		$expires = 'Wed, 01 Jan 2025 00:00:00 GMT';
		$response = (new HttpResponse())->withCookie('token', 'abc', [
			'expires' => $expires,
		]);

		$cookie = $response->toPsrResponse()->getHeaderLine('Set-Cookie');

		$this->assertStringContainsString('token=abc', $cookie);
		$this->assertStringContainsString('Expires=' . $expires, $cookie);
	}

	#[Test]
	public function with_cookie_without_optional_flags_has_no_secure_or_httponly(): void
	{
		$response = (new HttpResponse())->withCookie('basic', 'value');

		$cookie = $response->toPsrResponse()->getHeaderLine('Set-Cookie');

		$this->assertStringContainsString('basic=value', $cookie);
		$this->assertStringNotContainsString('Secure', $cookie);
		$this->assertStringNotContainsString('HttpOnly', $cookie);
	}

	#[Test]
	public function multiple_cookies_produce_multiple_set_cookie_headers(): void
	{
		$response = (new HttpResponse())
			->withCookie('a', '1')
			->withCookie('b', '2');

		$cookies = $response->toPsrResponse()->getHeader('Set-Cookie');

		$this->assertCount(2, $cookies);
	}

	#[Test]
	public function created_without_location_has_no_location_header(): void
	{
		$response = HttpResponse::created();

		$this->assertFalse($response->toPsrResponse()->hasHeader('Location'));
		$this->assertSame(201, $response->toPsrResponse()->getStatusCode());
	}

	#[Test]
	public function to_psr_response_returns_psr7_response_interface(): void
	{
		$psr = (new HttpResponse())->toPsrResponse();

		$this->assertInstanceOf(ResponseInterface::class, $psr);
	}

	#[Test]
	public function json_encodes_unicode_without_escape(): void
	{
		$response = HttpResponse::json(['name' => 'Ünïcödé']);
		$body = (string) $response->toPsrResponse()->getBody();

		$this->assertStringContainsString('Ünïcödé', $body);
	}
}
