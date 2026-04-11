<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router\Stubs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Stream;

/**
 * Stub handler that returns a 201 response with "create-user" body.
 */
class CreateUserHandler implements RequestHandlerInterface
{
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return new Response(201, '', body: Stream::createFromString('create-user'));
	}
}
