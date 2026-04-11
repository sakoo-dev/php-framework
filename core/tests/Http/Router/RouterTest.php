<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\Router\Exceptions\MethodNotAllowedException;
use Sakoo\Framework\Core\Http\Router\Exceptions\RouteNotFoundException;
use Sakoo\Framework\Core\Http\Router\HttpMethod;
use Sakoo\Framework\Core\Http\Router\Router;
use Sakoo\Framework\Core\Http\ServerRequest;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Uri;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\CreateUserHandler;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\ListUsersHandler;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\ShowUserHandler;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\StubMiddleware;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\UserController;
use Sakoo\Framework\Core\Tests\TestCase;

final class RouterTest extends TestCase
{
	private Router $router;

	protected function setUp(): void
	{
		parent::setUp();

		$this->router = new Router();

		container()->bind(ListUsersHandler::class, ListUsersHandler::class);
		container()->bind(CreateUserHandler::class, CreateUserHandler::class);
		container()->bind(ShowUserHandler::class, ShowUserHandler::class);
		container()->bind(StubMiddleware::class, StubMiddleware::class);
		container()->bind(UserController::class, UserController::class);
	}

	private function request(string $method, string $path): ServerRequest
	{
		return new ServerRequest(
			$method,
			Uri::fromString('http://localhost' . $path),
			new HeaderBag(),
			Stream::createFromString(),
		);
	}

	#[Test]
	public function it_matches_get_route(): void
	{
		$this->router->get('/users', ListUsersHandler::class);

		$response = $this->router->handle($this->request('GET', '/users'));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('list-users', (string) $response->getBody());
	}

	#[Test]
	public function it_matches_post_route(): void
	{
		$this->router->post('/users', CreateUserHandler::class);

		$response = $this->router->handle($this->request('POST', '/users'));

		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('create-user', (string) $response->getBody());
	}

	#[Test]
	public function it_captures_path_parameters(): void
	{
		$this->router->get('/users/{id}', ShowUserHandler::class);

		$response = $this->router->handle($this->request('GET', '/users/42'));

		$this->assertSame('user-42', (string) $response->getBody());
	}

	#[Test]
	public function it_throws_route_not_found_for_unknown_path(): void
	{
		$this->router->get('/users', ListUsersHandler::class);

		$this->expectException(RouteNotFoundException::class);
		$this->router->handle($this->request('GET', '/posts'));
	}

	#[Test]
	public function it_throws_method_not_allowed_when_path_matches_but_method_differs(): void
	{
		$this->router->get('/users', ListUsersHandler::class);

		try {
			$this->router->handle($this->request('DELETE', '/users'));
			$this->fail('Expected MethodNotAllowedException');
		} catch (MethodNotAllowedException $e) {
			$allowed = $e->getAllowedMethods();
			$this->assertCount(1, $allowed);
			$this->assertSame(HttpMethod::GET, $allowed[0]);
		}
	}

	#[Test]
	public function it_registers_routes_via_convenience_methods(): void
	{
		$this->router->get('/a', ListUsersHandler::class);
		$this->router->post('/a', CreateUserHandler::class);
		$this->router->put('/a', ListUsersHandler::class);
		$this->router->patch('/a', ListUsersHandler::class);
		$this->router->delete('/a', ListUsersHandler::class);

		$this->assertCount(5, $this->router->getRoutes());
	}

	#[Test]
	public function it_applies_per_route_middleware(): void
	{
		$this->router->get('/users', ListUsersHandler::class, [StubMiddleware::class]);

		$response = $this->router->handle($this->request('GET', '/users'));

		$this->assertTrue($response->hasHeader('X-Middleware'));
		$this->assertSame('applied', $response->getHeaderLine('X-Middleware'));
	}

	#[Test]
	public function it_applies_group_middleware(): void
	{
		$this->router->group([StubMiddleware::class], function (Router $router): void {
			$router->get('/users', ListUsersHandler::class);
		});

		$response = $this->router->handle($this->request('GET', '/users'));

		$this->assertTrue($response->hasHeader('X-Middleware'));
	}

	#[Test]
	public function group_middleware_does_not_leak_to_routes_outside_group(): void
	{
		$this->router->group([StubMiddleware::class], function (Router $router): void {
			$router->get('/inside', ListUsersHandler::class);
		});

		$this->router->get('/outside', ListUsersHandler::class);

		$response = $this->router->handle($this->request('GET', '/outside'));

		$this->assertFalse($response->hasHeader('X-Middleware'));
	}

	#[Test]
	public function it_dispatches_multi_action_controller_index(): void
	{
		$this->router->get('/users', [UserController::class, 'index']);

		$response = $this->router->handle($this->request('GET', '/users'));

		$this->assertSame(200, $response->getStatusCode());

		/** @var array{action: string} $body */
		$body = json_decode((string) $response->getBody(), true);
		$this->assertSame('index', $body['action']);
	}

	#[Test]
	public function it_dispatches_multi_action_controller_show_with_params(): void
	{
		$this->router->get('/users/{id}', [UserController::class, 'show']);

		$response = $this->router->handle($this->request('GET', '/users/99'));

		/** @var array{action: string, id: string} $body */
		$body = json_decode((string) $response->getBody(), true);
		$this->assertSame('show', $body['action']);
		$this->assertSame('99', $body['id']);
	}

	#[Test]
	public function it_dispatches_multi_action_controller_store(): void
	{
		$this->router->post('/users', [UserController::class, 'store']);

		$response = $this->router->handle($this->request('POST', '/users'));

		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('/users/1', $response->getHeaderLine('Location'));
	}

	#[Test]
	public function it_dispatches_multi_action_controller_update(): void
	{
		$this->router->put('/users/{id}', [UserController::class, 'update']);

		$response = $this->router->handle($this->request('PUT', '/users/7'));

		/** @var array{action: string, id: string} $body */
		$body = json_decode((string) $response->getBody(), true);
		$this->assertSame('update', $body['action']);
		$this->assertSame('7', $body['id']);
	}

	#[Test]
	public function it_dispatches_multi_action_controller_destroy(): void
	{
		$this->router->delete('/users/{id}', [UserController::class, 'destroy']);

		$response = $this->router->handle($this->request('DELETE', '/users/7'));

		$this->assertSame(204, $response->getStatusCode());
	}

	#[Test]
	public function it_applies_middleware_to_multi_action_routes(): void
	{
		$this->router->get('/users', [UserController::class, 'index'], [StubMiddleware::class]);

		$response = $this->router->handle($this->request('GET', '/users'));

		$this->assertTrue($response->hasHeader('X-Middleware'));
		$this->assertSame('applied', $response->getHeaderLine('X-Middleware'));
	}

	#[Test]
	public function it_mixes_single_and_multi_action_routes(): void
	{
		$this->router->get('/legacy', ListUsersHandler::class);
		$this->router->get('/modern', [UserController::class, 'index']);

		$legacy = $this->router->handle($this->request('GET', '/legacy'));
		$this->assertSame('list-users', (string) $legacy->getBody());

		$modern = $this->router->handle($this->request('GET', '/modern'));
		/** @var array{action: string} $body */
		$body = json_decode((string) $modern->getBody(), true);
		$this->assertSame('index', $body['action']);
	}

	#[Test]
	public function it_matches_route_with_trailing_slash(): void
	{
		$this->router->get('/users', ListUsersHandler::class);

		$response = $this->router->handle($this->request('GET', '/users/'));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('list-users', (string) $response->getBody());
	}

	#[Test]
	public function it_matches_nested_route_with_trailing_slash(): void
	{
		$this->router->get('/api/users', [UserController::class, 'index']);

		$response = $this->router->handle($this->request('GET', '/api/users/'));

		/** @var array{action: string} $body */
		$body = json_decode((string) $response->getBody(), true);
		$this->assertSame('index', $body['action']);
	}

	#[Test]
	public function it_preserves_root_path(): void
	{
		$this->router->get('/', ListUsersHandler::class);

		$response = $this->router->handle($this->request('GET', '/'));

		$this->assertSame(200, $response->getStatusCode());
	}
}
