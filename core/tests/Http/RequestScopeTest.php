<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Middleware\Middleware;
use Sakoo\Framework\Core\Http\Middleware\MiddlewarePipeline;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Profiler\ProfilerInterface;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Verifies the boundary between shared process-scoped state and per-request
 * user-scoped state under Sakoo's coroutine-first architecture.
 *
 * Shared scope (process-level, singleton):
 *   - Kernel, Container, Profiler, Router — created once at boot, live for
 *     the entire process lifetime, shared across all concurrent requests.
 *   - ConcurrencyCounter / Profiler::requestStarted — accumulates across requests.
 *
 * User scope (per-request, ephemeral):
 *   - HttpRequest, HttpResponse, Controller instances — created fresh per
 *     request, garbage-collected when the request ends.
 *   - Request attributes, parsed body, query params — isolated per request.
 *   - Middleware $next closure — captures its own pipeline index.
 */
final class RequestScopeTest extends TestCase
{
	private function psrRequest(string $path = '/', string $method = 'GET'): ServerRequest
	{
		return new ServerRequest(
			$method,
			Uri::fromString('http://localhost' . $path),
			new HeaderBag(),
			Stream::createFromString(),
		);
	}

	#[Test]
	public function profiler_concurrency_is_shared_across_requests(): void
	{
		$profiler = resolve(ProfilerInterface::class);
		$before = $profiler->totalRequests();

		$profiler->requestStarted();
		$profiler->requestFinished();
		$profiler->requestStarted();
		$profiler->requestFinished();

		$this->assertSame($before + 2, $profiler->totalRequests());
	}

	#[Test]
	public function profiler_peak_tracks_maximum_concurrent(): void
	{
		$profiler = resolve(ProfilerInterface::class);

		$profiler->requestStarted();
		$profiler->requestStarted();
		$profiler->requestStarted();
		$peakAfterThree = $profiler->peakRequests();

		$profiler->requestFinished();
		$profiler->requestFinished();
		$profiler->requestFinished();

		$this->assertGreaterThanOrEqual(3, $peakAfterThree);
		$this->assertSame($peakAfterThree, $profiler->peakRequests());
	}

	#[Test]
	public function http_request_attributes_are_isolated_per_instance(): void
	{
		$psr = $this->psrRequest('/users');

		$requestA = new HttpRequest($psr->withAttribute('user_id', 'alice'));
		$requestB = new HttpRequest($psr->withAttribute('user_id', 'bob'));

		$this->assertSame('alice', $requestA->routeParam('user_id'));
		$this->assertSame('bob', $requestB->routeParam('user_id'));

		$requestC = $requestA->withAttribute('role', 'admin');
		$this->assertNull($requestA->routeParam('role'));
		$this->assertSame('admin', $requestC->routeParam('role'));
	}

	#[Test]
	public function http_response_state_is_isolated_per_instance(): void
	{
		$responseA = HttpResponse::json(['user' => 'alice']);
		$responseB = HttpResponse::json(['user' => 'bob']);

		$responseA->withHeader('X-Session', 'session-a');

		$this->assertFalse($responseB->hasHeader('X-Session'));
	}

	#[Test]
	public function middleware_does_not_leak_state_between_requests(): void
	{
		$counter = new class {
			public int $calls = 0;
		};

		$countingMiddleware = new class($counter) extends Middleware {
			public function __construct(private readonly object $counter) {}

			public function handle(HttpRequest $request, \Closure $next): HttpResponse
			{
				++$this->counter->calls;
				$request = $request->withAttribute('call_number', $this->counter->calls);

				return $next($request);
			}
		};

		$echoHandler = new class implements RequestHandlerInterface {
			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				$callNum = (int) $request->getAttribute('call_number', 0);

				return new Response(200, '', body: Stream::createFromString((string) $callNum));
			}
		};

		container()->bind($countingMiddleware::class, fn () => $countingMiddleware);

		$pipeline1 = new MiddlewarePipeline($echoHandler, [$countingMiddleware::class]);
		$result1 = $pipeline1->handle($this->psrRequest('/a'));

		$pipeline2 = new MiddlewarePipeline($echoHandler, [$countingMiddleware::class]);
		$result2 = $pipeline2->handle($this->psrRequest('/b'));

		$this->assertSame('1', (string) $result1->getBody());
		$this->assertSame('2', (string) $result2->getBody());

		$this->assertSame(2, $counter->calls);
	}

	#[Test]
	public function concurrent_requests_see_own_query_params(): void
	{
		$psrA = $this->psrRequest('/search')->withQueryParams(['q' => 'php']);
		$psrB = $this->psrRequest('/search')->withQueryParams(['q' => 'swoole']);

		$requestA = new HttpRequest($psrA);
		$requestB = new HttpRequest($psrB);

		$this->assertSame('php', $requestA->query('q'));
		$this->assertSame('swoole', $requestB->query('q'));

		$this->assertNull($requestA->query('nonexistent'));
		$this->assertNull($requestB->query('nonexistent'));
	}

	#[Test]
	public function profiler_is_same_singleton_instance(): void
	{
		$a = resolve(ProfilerInterface::class);
		$b = resolve(ProfilerInterface::class);

		$this->assertSame($a, $b);
	}
}
