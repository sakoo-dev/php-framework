<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\Middleware\MiddlewarePipeline;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\Http\Middleware\Stubs\ThrowingMiddleware;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\ShortCircuitMiddleware;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\StubMiddleware;
use Sakoo\Framework\Core\Tests\TestCase;

final class MiddlewarePipelineTest extends TestCase
{
	private ServerRequest $request;

	protected function setUp(): void
	{
		parent::setUp();

		$this->request = new ServerRequest(
			'GET',
			Uri::fromString('http://localhost/test'),
			new HeaderBag(),
			Stream::createFromString(),
		);

		container()->bind(StubMiddleware::class, StubMiddleware::class);
		container()->bind(ShortCircuitMiddleware::class, ShortCircuitMiddleware::class);
		container()->bind(ThrowingMiddleware::class, ThrowingMiddleware::class);
	}

	private function fallbackHandler(int $status = 200, string $body = 'fallback'): RequestHandlerInterface
	{
		return new class($status, $body) implements RequestHandlerInterface {
			public function __construct(
				private readonly int $status,
				private readonly string $body,
			) {}

			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				return new Response($this->status, '', body: Stream::createFromString($this->body));
			}
		};
	}

	#[Test]
	public function it_invokes_fallback_when_no_middleware(): void
	{
		$pipeline = new MiddlewarePipeline($this->fallbackHandler());

		$response = $pipeline->handle($this->request);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('fallback', (string) $response->getBody());
	}

	#[Test]
	public function it_runs_middleware_in_order(): void
	{
		$pipeline = new MiddlewarePipeline(
			$this->fallbackHandler(),
			[StubMiddleware::class],
		);

		$response = $pipeline->handle($this->request);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertTrue($response->hasHeader('X-Middleware'));
		$this->assertSame('applied', $response->getHeaderLine('X-Middleware'));
	}

	#[Test]
	public function it_short_circuits_when_middleware_does_not_call_handler(): void
	{
		$pipeline = new MiddlewarePipeline(
			$this->fallbackHandler(),
			[ShortCircuitMiddleware::class, StubMiddleware::class],
		);

		$response = $pipeline->handle($this->request);

		$this->assertSame(403, $response->getStatusCode());
		$this->assertSame('blocked', (string) $response->getBody());
		$this->assertFalse($response->hasHeader('X-Middleware'));
	}

	#[Test]
	public function pipe_returns_new_pipeline(): void
	{
		$pipeline = new MiddlewarePipeline($this->fallbackHandler());
		$new = $pipeline->pipe(StubMiddleware::class);

		$responseOriginal = $pipeline->handle($this->request);
		$responseNew = $new->handle($this->request);

		$this->assertFalse($responseOriginal->hasHeader('X-Middleware'));
		$this->assertTrue($responseNew->hasHeader('X-Middleware'));
	}

	#[Test]
	public function it_propagates_exception_from_middleware(): void
	{
		$pipeline = new MiddlewarePipeline(
			$this->fallbackHandler(),
			[ThrowingMiddleware::class],
		);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('middleware error');
		$pipeline->handle($this->request);
	}
}
