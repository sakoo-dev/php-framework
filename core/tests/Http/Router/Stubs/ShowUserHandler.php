<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router\Stubs;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Stream;

/**
 * Stub handler that echoes back the "id" request attribute as the response body.
 */
class ShowUserHandler implements RequestHandlerInterface
{
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$id = (string) $request->getAttribute('id', '');

		return new Response(200, '', body: Stream::createFromString('user-' . $id));
	}
}
