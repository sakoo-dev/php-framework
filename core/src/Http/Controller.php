<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Abstract base controller providing response builder helpers.
 *
 * Supports two usage patterns:
 *
 * 1. Single-action (invokable) — override __invoke(HttpRequest) and register
 *    the class name as the route handler:
 *
 *        class HealthCheckController extends Controller
 *        {
 *            public function __invoke(HttpRequest $request): HttpResponse
 *            {
 *                return $this->json(['status' => 'ok']);
 *            }
 *        }
 *
 *        $router->get('/health', HealthCheckController::class);
 *
 * 2. Multi-action — define multiple public methods and register each as a
 *    [Controller, 'method'] pair:
 *
 *        class UserController extends Controller
 *        {
 *            public function index(HttpRequest $request): HttpResponse
 *            {
 *                return $this->json(['users' => []]);
 *            }
 *
 *            public function show(HttpRequest $request): HttpResponse
 *            {
 *                $id = $request->routeParam('id');
 *                return $this->json(['id' => $id]);
 *            }
 *        }
 *
 *        $router->get('/users', [UserController::class, 'index']);
 *        $router->get('/users/{id}', [UserController::class, 'show']);
 *
 * Both patterns receive an HttpRequest and return HttpResponse (or a raw
 * PSR-7 ResponseInterface). The base class provides json(), text(), html(),
 * redirect(), noContent(), and created() helpers.
 */
abstract class Controller implements RequestHandlerInterface
{
	/**
	 * Dispatches a named action method on this controller. Used by the Router
	 * for multi-action [Controller, 'method'] routes. Wraps the PSR-7 request
	 * in an HttpRequest, calls the method, and unwraps the result.
	 *
	 * @throws \BadMethodCallException
	 */
	public function callAction(string $action, ServerRequestInterface $request): ResponseInterface
	{
		if (!method_exists($this, $action)) {
			throw new \BadMethodCallException("Action [{$action}] does not exist on " . static::class);
		}

		$httpRequest = new HttpRequest($request);

		/** @var HttpResponse|ResponseInterface $result */
		$result = $this->{$action}($httpRequest);

		if ($result instanceof HttpResponse) {
			return $result->toPsrResponse();
		}

		return $result;
	}

	/**
	 * PSR-15 entry point for single-action (invokable) controllers.
	 * Wraps the PSR-7 request and delegates to __invoke(). Multi-action
	 * controllers should not rely on this — the Router calls callAction()
	 * directly for named methods.
	 *
	 * @throws \BadMethodCallException
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		if (!method_exists($this, '__invoke')) {
			throw new \BadMethodCallException(
				static::class . ' must implement __invoke() for single-action routing, '
				. 'or be registered as [Controller::class, \'method\'] for multi-action routing.',
			);
		}

		return $this->callAction('__invoke', $request);
	}

	/**
	 * Creates a JSON response.
	 *
	 * @param array<mixed>|object $data
	 *
	 * @throws \JsonException
	 */
	protected function json(array|object $data, int $status = 200): HttpResponse
	{
		return HttpResponse::json($data, $status);
	}

	/**
	 * Creates a plain text response.
	 */
	protected function text(string $content, int $status = 200): HttpResponse
	{
		return HttpResponse::text($content, $status);
	}

	/**
	 * Creates an HTML response.
	 */
	protected function html(string $content, int $status = 200): HttpResponse
	{
		return HttpResponse::html($content, $status);
	}

	/**
	 * Creates a redirect response.
	 */
	protected function redirect(string $url, int $status = 302): HttpResponse
	{
		return HttpResponse::redirect($url, $status);
	}

	/**
	 * Creates a 204 No Content response.
	 */
	protected function noContent(): HttpResponse
	{
		return HttpResponse::noContent();
	}

	/**
	 * Creates a 201 Created response.
	 *
	 * @param null|array<mixed>|object $data
	 *
	 * @throws \JsonException
	 */
	protected function created(string $location = '', array|object|null $data = null): HttpResponse
	{
		return HttpResponse::created($location, $data);
	}
}
