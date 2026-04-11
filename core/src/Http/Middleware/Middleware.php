<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Middleware;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;

/**
 * Abstract base middleware that bridges PSR-15 and the Sakoo convenience layer.
 *
 * Implements MiddlewareInterface so it plugs into the MiddlewarePipeline
 * transparently, but instead of forcing subclasses to work with raw PSR-7
 * interfaces, it wraps the incoming request in an HttpRequest and provides
 * a $next closure that accepts and returns HttpResponse.
 *
 * Subclasses implement handle(HttpRequest, Closure): HttpResponse:
 *
 *     class CorsMiddleware extends Middleware
 *     {
 *         public function handle(HttpRequest $request, Closure $next): HttpResponse
 *         {
 *             $response = $next($request);
 *
 *             return $response
 *                 ->withHeader('Access-Control-Allow-Origin', '*');
 *         }
 *     }
 *
 * The $next closure signature is: fn(HttpRequest): HttpResponse
 *
 * To short-circuit the pipeline (e.g. auth guard), return an HttpResponse
 * without calling $next:
 *
 *     public function handle(HttpRequest $request, Closure $next): HttpResponse
 *     {
 *         if (!$request->bearerToken()) {
 *             return HttpResponse::json(['error' => 'Unauthorized'], 401);
 *         }
 *
 *         return $next($request);
 *     }
 *
 * Request modification flows naturally — mutate the HttpRequest's underlying
 * PSR-7 object via the withPsr() helper before passing to $next:
 *
 *     $modified = $request->withPsr(
 *         $request->psrRequest()->withAttribute('user_id', $userId)
 *     );
 *     return $next($modified);
 */
abstract class Middleware implements MiddlewareInterface
{
	/**
	 * Subclasses implement this as their middleware logic. Receives an
	 * HttpRequest and a $next closure. Call $next($request) to continue
	 * the pipeline, or return an HttpResponse directly to short-circuit.
	 *
	 * @param \Closure(HttpRequest): HttpResponse $next
	 */
	abstract public function handle(HttpRequest $request, \Closure $next): HttpResponse;

	/**
	 * PSR-15 bridge: wraps the raw request, builds the $next closure,
	 * delegates to handle(), and unwraps the HttpResponse back to PSR-7.
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$httpRequest = new HttpRequest($request);

		$next = static function (HttpRequest $req) use ($handler): HttpResponse {
			$psrResponse = $handler->handle($req->psrRequest());

			return HttpResponse::fromPsr($psrResponse);
		};

		$result = $this->handle($httpRequest, $next);

		return $result->toPsrResponse();
	}
}
