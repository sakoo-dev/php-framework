<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router\Stubs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Stream;

/**
 * Stub middleware that short-circuits the pipeline and returns a 403 response
 * without calling the next handler.
 */
class ShortCircuitMiddleware implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		return new Response(403, '', body: Stream::createFromString('blocked'));
	}
}
