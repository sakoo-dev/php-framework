<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware dispatcher implementing a FIFO pipeline.
 *
 * Middleware classes are resolved from the container on dispatch, enabling
 * lazy instantiation and constructor injection. Each middleware receives the
 * request and a handler that delegates to the next middleware in the stack.
 * When all middleware have executed, the fallback handler is invoked.
 *
 * The pipeline is immutable after construction — adding middleware returns a
 * new MiddlewarePipeline instance rather than mutating the existing one.
 */
class MiddlewarePipeline implements RequestHandlerInterface
{
	/** @var array<class-string<MiddlewareInterface>> */
	private readonly array $middleware;

	/**
	 * @param RequestHandlerInterface                  $fallbackHandler Invoked after all middleware
	 * @param array<class-string<MiddlewareInterface>> $middleware
	 */
	public function __construct(
		private readonly RequestHandlerInterface $fallbackHandler,
		array $middleware = [],
		private readonly int $index = 0,
	) {
		$this->middleware = $middleware;
	}

	/**
	 * Returns a new pipeline with $middleware appended at the end.
	 *
	 * @param class-string<MiddlewareInterface> $middleware
	 */
	public function pipe(string $middleware): self
	{
		return new self($this->fallbackHandler, [...$this->middleware, $middleware]);
	}

	/**
	 * Dispatches the request through the middleware stack. Each middleware
	 * is resolved from the container so dependencies are injected automatically.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		if (!isset($this->middleware[$this->index])) {
			return $this->fallbackHandler->handle($request);
		}

		$middlewareClass = $this->middleware[$this->index];

		// @phpstan-ignore argument.type
		$middleware = resolve($middlewareClass);

		$next = new self($this->fallbackHandler, $this->middleware, $this->index + 1);

		// @var MiddlewareInterface $middleware
		return $middleware->process($request, $next);
	}
}
