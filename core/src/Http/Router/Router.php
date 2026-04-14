<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sakoo\Framework\Core\Http\Controller;
use Sakoo\Framework\Core\Http\Middleware\MiddlewarePipeline;
use Sakoo\Framework\Core\Http\Router\Exceptions\MethodNotAllowedException;
use Sakoo\Framework\Core\Http\Router\Exceptions\RouteNotFoundException;

/**
 * Typed route registry with method+pattern→controller mapping.
 *
 * Routes are registered via convenience methods (get, post, put, patch, delete)
 * or the generic addRoute(). Each route maps an HTTP method and a URI pattern
 * to a controller class (single-action invokable) or a [Controller, 'method']
 * pair (multi-action), resolved from the container at dispatch time.
 *
 * Trailing slashes are normalised automatically — /users and /users/ resolve
 * to the same route. The root path / is preserved as-is.
 *
 * Route handler formats:
 *
 *     // Single-action — calls __invoke()
 *     $router->get('/health', HealthCheckController::class);
 *
 *     // Multi-action — calls the named method
 *     $router->get('/users', [UserController::class, 'index']);
 *     $router->get('/users/{id}', [UserController::class, 'show']);
 *     $router->post('/users', [UserController::class, 'store']);
 *
 * The Router itself implements RequestHandlerInterface so it can serve as the
 * terminal handler in a middleware pipeline.
 *
 * @throws RouteNotFoundException    when no route matches the request path (404)
 * @throws MethodNotAllowedException when a route matches the path but not the method (405)
 */
class Router implements RequestHandlerInterface
{
	/** @var Route[] */
	private array $routes = [];

	/** @var array<class-string<MiddlewareInterface>> */
	private array $groupMiddleware = [];

	/**
	 * @param array{0: class-string, 1: string}|class-string $handler
	 * @param array<class-string<MiddlewareInterface>>       $middleware
	 */
	public function get(string $pattern, array|string $handler, array $middleware = []): void
	{
		$this->addRoute(HttpMethod::GET, $pattern, $handler, $middleware);
	}

	/**
	 * @param array{0: class-string, 1: string}|class-string $handler
	 * @param array<class-string<MiddlewareInterface>>       $middleware
	 */
	public function post(string $pattern, array|string $handler, array $middleware = []): void
	{
		$this->addRoute(HttpMethod::POST, $pattern, $handler, $middleware);
	}

	/**
	 * @param array{0: class-string, 1: string}|class-string $handler
	 * @param array<class-string<MiddlewareInterface>>       $middleware
	 */
	public function put(string $pattern, array|string $handler, array $middleware = []): void
	{
		$this->addRoute(HttpMethod::PUT, $pattern, $handler, $middleware);
	}

	/**
	 * @param array{0: class-string, 1: string}|class-string $handler
	 * @param array<class-string<MiddlewareInterface>>       $middleware
	 */
	public function patch(string $pattern, array|string $handler, array $middleware = []): void
	{
		$this->addRoute(HttpMethod::PATCH, $pattern, $handler, $middleware);
	}

	/**
	 * @param array{0: class-string, 1: string}|class-string $handler
	 * @param array<class-string<MiddlewareInterface>>       $middleware
	 */
	public function delete(string $pattern, array|string $handler, array $middleware = []): void
	{
		$this->addRoute(HttpMethod::DELETE, $pattern, $handler, $middleware);
	}

	/**
	 * Registers a route for the given method, pattern, and handler.
	 *
	 * @param array{0: class-string, 1: string}|class-string $handler
	 * @param array<class-string<MiddlewareInterface>>       $middleware
	 */
	public function addRoute(HttpMethod $method, string $pattern, array|string $handler, array $middleware = []): void
	{
		$allMiddleware = array_merge($this->groupMiddleware, $middleware);

		if (is_array($handler)) {
			$this->routes[] = new Route($method, $pattern, $handler[0], $handler[1], $allMiddleware);
		} else {
			$this->routes[] = new Route($method, $pattern, $handler, null, $allMiddleware);
		}
	}

	/**
	 * Applies middleware to all routes registered inside $callback.
	 *
	 * @param array<class-string<MiddlewareInterface>> $middleware
	 */
	public function group(array $middleware, callable $callback): void
	{
		$previous = $this->groupMiddleware;
		$this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);

		$callback($this);

		$this->groupMiddleware = $previous;
	}

	/**
	 * Matches the request against registered routes and dispatches the controller.
	 *
	 * Trailing slashes are stripped before matching so /path and /path/ resolve
	 * identically. The root path / is preserved.
	 *
	 * @throws RouteNotFoundException
	 * @throws MethodNotAllowedException
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$method = HttpMethod::from($request->getMethod());
		$path = self::normalizePath($request->getUri()->getPath());

		$matchedRoute = null;
		$params = [];
		$allowedMethods = [];

		foreach ($this->routes as $route) {
			$routeParams = $route->match($path);

			if (null === $routeParams) {
				continue;
			}

			$allowedMethods[] = $route->method;

			if ($route->method === $method) {
				$matchedRoute = $route;
				$params = $routeParams;

				break;
			}
		}

		if (null === $matchedRoute && [] !== $allowedMethods) {
			throw new MethodNotAllowedException($allowedMethods);
		}

		if (null === $matchedRoute) {
			throw new RouteNotFoundException($path);
		}

		foreach ($params as $name => $value) {
			$request = $request->withAttribute($name, $value);
		}

		$actionHandler = $this->resolveHandler($matchedRoute);

		if ([] !== $matchedRoute->middleware) {
			$pipeline = new MiddlewarePipeline($actionHandler, $matchedRoute->middleware);

			return $pipeline->handle($request);
		}

		return $actionHandler->handle($request);
	}

	/**
	 * Returns all registered routes.
	 *
	 * @return Route[]
	 */
	public function getRoutes(): array
	{
		return $this->routes;
	}

	/**
	 * Strips trailing slashes from the path while preserving root /.
	 * Both /metrics and /metrics/ match the same route.
	 */
	private static function normalizePath(string $path): string
	{
		if ('/' === $path || '' === $path) {
			return '/';
		}

		return rtrim($path, '/');
	}

	/**
	 * Resolves a Route into a RequestHandlerInterface. For Controller subclasses
	 * with a named action, wraps the dispatch via callAction(). For plain
	 * RequestHandlerInterface classes or single-action Controllers, delegates
	 * to handle() directly.
	 */
	private function resolveHandler(Route $route): RequestHandlerInterface
	{
		$controller = resolve($route->handler);

		if (null !== $route->action && $controller instanceof Controller) {
			$action = $route->action;

			return new readonly class($controller, $action) implements RequestHandlerInterface {
				public function __construct(
					private Controller $controller,
					private string $action,
				) {}

				public function handle(ServerRequestInterface $request): ResponseInterface
				{
					return $this->controller->callAction($this->action, $request);
				}
			};
		}

		if ($controller instanceof RequestHandlerInterface) {
			return $controller;
		}

		throw new \RuntimeException("Handler {$route->handler} must implement RequestHandlerInterface or extend Controller.");
	}
}
