<?php

declare(strict_types=1);

namespace System\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Middleware\MiddlewarePipeline;
use Sakoo\Framework\Core\Http\Router\Exceptions\MethodNotAllowedException;
use Sakoo\Framework\Core\Http\Router\Exceptions\RouteNotFoundException;
use Sakoo\Framework\Core\Http\Router\Router;
use System\ServiceLoader\HttpServiceLoader;

/**
 * HTTP kernel that dispatches PSR-7 server requests through the global
 * middleware pipeline and into the Router.
 *
 * Route registration is the responsibility of the service loader. The kernel
 * receives a fully populated Router and does not load routes itself, allowing
 * the loader to apply caching strategies (e.g. RouterCache) before handing
 * the router over.
 */
final class HttpKernel
{
	/**
	 * Stores the router and global middleware stack, then loads all application
	 * routes into the router via HttpServiceLoader.
	 *
	 * @phpstan-param array<class-string> $globalMiddlewares
	 */
	public function __construct(private readonly Router $router, private readonly array $globalMiddlewares)
	{
		HttpServiceLoader::loadRoutes($this->router);
	}

	/**
	 * Runs the request through the global middleware pipeline and returns a PSR-7
	 * response. Catches routing exceptions (404, 405) and any other Throwable (500),
	 * returning appropriate text responses. In debug mode the raw exception message
	 * is forwarded; in production a generic message is used.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		try {
			return (new MiddlewarePipeline($this->router, $this->globalMiddlewares))->handle($request);
		} catch (MethodNotAllowedException $exception) {
			return HttpResponse::text($exception->getMessage(), 405)->toPsrResponse();
		} catch (RouteNotFoundException $exception) {
			return HttpResponse::text($exception->getMessage(), 404)->toPsrResponse();
		} catch (\Throwable $exception) {
			$message = kernel()->isInDebugEnv() ? $exception->getMessage() : 'Internal Server Error';

			return HttpResponse::text($message, 500)->toPsrResponse();
		}
	}
}
