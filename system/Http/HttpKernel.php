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
use Sakoo\Framework\Core\ServiceLoader\HttpServiceLoader;

final class HttpKernel
{
	/** @param array<class-string> $globalMiddlewares */
	public function __construct(private Router $router, private array $globalMiddlewares)
	{
		HttpServiceLoader::loadRoutes($this->router);
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		try {
			return (new MiddlewarePipeline($this->router, $this->globalMiddlewares))->handle($request);
		} catch (MethodNotAllowedException $exception) {
			return HttpResponse::text($exception->getMessage(), 405)->toPsrResponse();
		} catch (RouteNotFoundException $exception) {
			return HttpResponse::text($exception->getMessage(), 404)->toPsrResponse();
		} catch (\Throwable $exception) {
			return HttpResponse::text($exception->getMessage(), 500)->toPsrResponse();
		}
	}
}
