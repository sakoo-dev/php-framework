<?php

declare(strict_types=1);

namespace System\Middleware;

use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Middleware\Middleware;

/**
 * Adds a unique X-Request-Id header to every response for distributed tracing.
 *
 * If the incoming request already carries an X-Request-Id header (e.g. from a
 * load balancer or API gateway), that value is preserved instead of generating
 * a new one, maintaining trace continuity across service boundaries.
 *
 * The request ID is also set as a request attribute ('request_id') so
 * controllers and downstream middleware can access it via
 * $request->routeParam('request_id').
 */
class RequestIdMiddleware extends Middleware
{
	public function handle(HttpRequest $request, \Closure $next): HttpResponse
	{
		$requestId = $request->hasHeader('X-Request-Id')
			? $request->header('X-Request-Id')
			: bin2hex(random_bytes(16));

		$request = $request->withAttribute('request_id', $requestId);

		$response = $next($request);

		return $response->withHeader('X-Request-Id', $requestId);
	}
}
