<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Controller;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Tests for the Controller base-class exception branches and edge cases not
 * covered by RouterTest or ControllerTest (which focus on happy paths).
 */
final class ControllerEdgeCasesTest extends TestCase
{
	private function psrRequest(string $path = '/'): ServerRequest
	{
		return new ServerRequest(
			'GET',
			Uri::fromString('http://localhost' . $path),
			new HeaderBag(),
			Stream::createFromString(),
		);
	}

	#[Test]
	public function handle_throws_when_invoke_not_defined(): void
	{
		$controller = new class extends Controller {
			public function index(HttpRequest $request): HttpResponse
			{
				return $this->json(['ok' => true]);
			}
		};

		$this->expectException(\BadMethodCallException::class);
		$controller->handle($this->psrRequest());
	}

	#[Test]
	public function call_action_throws_when_method_does_not_exist(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->noContent();
			}
		};

		$this->expectException(\BadMethodCallException::class);
		$controller->callAction('nonExistentMethod', $this->psrRequest());
	}

	#[Test]
	public function call_action_exception_message_contains_action_name(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->noContent();
			}
		};

		try {
			$controller->callAction('ghost', $this->psrRequest());
			$this->fail('Expected BadMethodCallException');
		} catch (\BadMethodCallException $e) {
			$this->assertStringContainsString('ghost', $e->getMessage());
		}
	}

	#[Test]
	public function handle_exception_message_contains_class_context(): void
	{
		$controller = new class extends Controller {};

		try {
			$controller->handle($this->psrRequest());
			$this->fail('Expected BadMethodCallException');
		} catch (\BadMethodCallException $e) {
			$this->assertStringContainsString('__invoke', $e->getMessage());
		}
	}

	#[Test]
	public function call_action_unwraps_http_response_to_psr(): void
	{
		$controller = new class extends Controller {
			public function greet(HttpRequest $request): HttpResponse
			{
				return $this->text('hello');
			}
		};

		$psr = $controller->callAction('greet', $this->psrRequest());

		$this->assertSame(200, $psr->getStatusCode());
		$this->assertSame('hello', (string) $psr->getBody());
	}

	#[Test]
	public function call_action_passes_request_attributes_to_action(): void
	{
		$controller = new class extends Controller {
			public function show(HttpRequest $request): HttpResponse
			{
				return $this->json(['id' => $request->routeParam('id')]);
			}
		};

		$psrWithAttr = $this->psrRequest('/users/7')->withAttribute('id', '7');
		$psr = $controller->callAction('show', $psrWithAttr);

		$this->assertSame('{"id":"7"}', (string) $psr->getBody());
	}
}
