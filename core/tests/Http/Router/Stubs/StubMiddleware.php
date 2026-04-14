<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router\Stubs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stub middleware that adds an X-Before header to the request and an
 * X-After header to the response, proving middleware ordering.
 */
class StubMiddleware implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$request = $request->withAttribute('middleware_touched', true);
		$response = $handler->handle($request);

		return $response->withHeader('X-Middleware', 'applied');
	}
}
