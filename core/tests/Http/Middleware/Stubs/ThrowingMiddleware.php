<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Middleware\Stubs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Stub middleware that always throws a RuntimeException to test
 * exception propagation through the pipeline.
 */
class ThrowingMiddleware implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		throw new \RuntimeException('middleware error');
	}
}
