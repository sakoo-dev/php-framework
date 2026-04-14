<?php

declare(strict_types=1);

namespace System\Middleware;

use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;
use Sakoo\Framework\Core\Http\Middleware\Middleware;

/**
 * Sanitises request input and hardens response headers against XSS attacks.
 *
 * On the request side: strips HTML tags from all query parameters and parsed
 * body values, preventing reflected XSS from reaching controllers.
 *
 * On the response side: adds security headers that instruct browsers to block
 * cross-site scripting vectors:
 *
 * - X-Content-Type-Options: nosniff
 * - X-Frame-Options: DENY
 * - X-XSS-Protection: 1; mode=block
 * - Referrer-Policy: strict-origin-when-cross-origin
 * - Content-Security-Policy: default-src 'self'
 */
class XssProtectionMiddleware extends Middleware
{
	public function handle(HttpRequest $request, \Closure $next): HttpResponse
	{
		$request = $this->sanitizeRequest($request);

		$response = $next($request);

		return $this->hardenResponse($response);
	}

	private function sanitizeRequest(HttpRequest $request): HttpRequest
	{
		$psr = $request->psrRequest();

		$query = $this->sanitizeArray($request->queryAll());
		$psr = $psr->withQueryParams($query);

		$body = $psr->getParsedBody();

		if (is_array($body)) {
			$psr = $psr->withParsedBody($this->sanitizeArray($body));
		}

		return $request->withPsr($psr);
	}

	/**
	 * @param array<mixed> $data
	 *
	 * @return array<mixed>
	 */
	private function sanitizeArray(array $data): array
	{
		$sanitized = [];

		foreach ($data as $key => $value) {
			if (is_string($value)) {
				$sanitized[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			} elseif (is_array($value)) {
				$sanitized[$key] = $this->sanitizeArray($value);
			} else {
				$sanitized[$key] = $value;
			}
		}

		return $sanitized;
	}

	private function hardenResponse(HttpResponse $response): HttpResponse
	{
		return $response
			->withHeader('X-Content-Type-Options', 'nosniff')
			->withHeader('X-Frame-Options', 'DENY')
			->withHeader('X-XSS-Protection', '1; mode=block')
			->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
			->withHeader('Content-Security-Policy', "default-src 'self'");
	}
}
