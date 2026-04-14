<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\Controller;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\TestCase;

final class ControllerTest extends TestCase
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

	private function httpRequest(string $path = '/'): HttpRequest
	{
		return new HttpRequest($this->psrRequest($path));
	}

	#[Test]
	public function single_action_handle_wraps_and_unwraps(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->json(['path' => $request->path()]);
			}
		};

		$response = $controller->handle($this->psrRequest('/test'));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
		$this->assertSame('{"path":"\/test"}', (string) $response->getBody());
	}

	#[Test]
	public function single_action_returns_raw_psr_response(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): ResponseInterface
			{
				return new Response(204);
			}
		};

		$response = $controller->handle($this->psrRequest());

		$this->assertSame(204, $response->getStatusCode());
	}

	#[Test]
	public function multi_action_method_returns_http_response(): void
	{
		$controller = new class extends Controller {
			public function index(HttpRequest $request): HttpResponse
			{
				return $this->json(['action' => 'index']);
			}

			public function show(HttpRequest $request): HttpResponse
			{
				$id = $request->routeParam('id');

				return $this->json(['action' => 'show', 'id' => $id]);
			}
		};

		$indexResult = $controller->index($this->httpRequest('/users'));
		$this->assertSame('{"action":"index"}', (string) $indexResult->toPsrResponse()->getBody());

		$showRequest = new HttpRequest($this->psrRequest('/users/42')->withAttribute('id', '42'));
		$showResult = $controller->show($showRequest);
		$this->assertSame('{"action":"show","id":"42"}', (string) $showResult->toPsrResponse()->getBody());
	}

	#[Test]
	public function json_helper(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->json(['ok' => true], 201);
			}
		};

		$response = $controller->handle($this->psrRequest());

		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('{"ok":true}', (string) $response->getBody());
	}

	#[Test]
	public function text_helper(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->text('plain content');
			}
		};

		$response = $controller->handle($this->psrRequest());

		$this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
		$this->assertSame('plain content', (string) $response->getBody());
	}

	#[Test]
	public function html_helper(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->html('<h1>Hello</h1>');
			}
		};

		$response = $controller->handle($this->psrRequest());

		$this->assertSame('text/html; charset=utf-8', $response->getHeaderLine('Content-Type'));
	}

	#[Test]
	public function redirect_helper(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->redirect('/dashboard');
			}
		};

		$response = $controller->handle($this->psrRequest());

		$this->assertSame(302, $response->getStatusCode());
		$this->assertSame('/dashboard', $response->getHeaderLine('Location'));
	}

	#[Test]
	public function no_content_helper(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->noContent();
			}
		};

		$this->assertSame(204, $controller->handle($this->psrRequest())->getStatusCode());
	}

	#[Test]
	public function created_helper(): void
	{
		$controller = new class extends Controller {
			public function __invoke(HttpRequest $request): HttpResponse
			{
				return $this->created('/items/1', ['id' => 1]);
			}
		};

		$response = $controller->handle($this->psrRequest());

		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('/items/1', $response->getHeaderLine('Location'));
		$this->assertSame('{"id":1}', (string) $response->getBody());
	}
}
