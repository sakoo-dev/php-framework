<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Router\HttpMethod;
use Sakoo\Framework\Core\Http\Router\Route;
use Sakoo\Framework\Core\Tests\Http\Router\Stubs\StubMiddleware;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Extended Route value-object tests covering action, middleware, handler
 * properties and additional matching edge cases not in RouteTest.
 */
final class RouteExtendedTest extends TestCase
{
	#[Test]
	public function it_stores_method_pattern_handler(): void
	{
		$route = new Route(HttpMethod::POST, '/articles', 'ArticleHandler');

		$this->assertSame(HttpMethod::POST, $route->method);
		$this->assertSame('/articles', $route->pattern);
		$this->assertSame('ArticleHandler', $route->handler);
	}

	#[Test]
	public function action_defaults_to_null(): void
	{
		$route = new Route(HttpMethod::GET, '/health', 'HealthHandler');

		$this->assertNull($route->action);
	}

	#[Test]
	public function action_is_stored_when_provided(): void
	{
		$route = new Route(HttpMethod::PUT, '/users/{id}', 'UserController', 'update');

		$this->assertSame('update', $route->action);
	}

	#[Test]
	public function middleware_defaults_to_empty_array(): void
	{
		$route = new Route(HttpMethod::GET, '/', 'Handler');

		$this->assertSame([], $route->middleware);
	}

	#[Test]
	public function middleware_is_stored_when_provided(): void
	{
		$route = new Route(
			HttpMethod::GET,
			'/secure',
			'SecureHandler',
			null,
			[StubMiddleware::class],
		);

		$this->assertSame([StubMiddleware::class], $route->middleware);
	}

	#[Test]
	public function it_matches_root_path(): void
	{
		$route = new Route(HttpMethod::GET, '/', 'HomeHandler');

		$this->assertSame([], $route->match('/'));
	}

	#[Test]
	public function it_does_not_match_root_against_non_root(): void
	{
		$route = new Route(HttpMethod::GET, '/', 'HomeHandler');

		$this->assertNull($route->match('/other'));
	}

	#[Test]
	public function it_matches_nested_path_with_one_segment(): void
	{
		$route = new Route(HttpMethod::GET, '/api/v1/status', 'StatusHandler');

		$this->assertSame([], $route->match('/api/v1/status'));
		$this->assertNull($route->match('/api/v1'));
		$this->assertNull($route->match('/api/v1/status/extra'));
	}

	#[Test]
	public function cached_pattern_is_reused_on_second_match(): void
	{
		$route = new Route(HttpMethod::GET, '/items/{id}', 'ItemHandler');

		$first = $route->match('/items/1');
		$second = $route->match('/items/2');

		$this->assertSame(['id' => '1'], $first);
		$this->assertSame(['id' => '2'], $second);
	}

	#[Test]
	public function parameter_with_hyphens_in_value_is_captured(): void
	{
		$route = new Route(HttpMethod::GET, '/slugs/{slug}', 'SlugHandler');

		$params = $route->match('/slugs/my-article-title');

		$this->assertSame(['slug' => 'my-article-title'], $params);
	}

	#[Test]
	public function parameter_does_not_capture_slash(): void
	{
		$route = new Route(HttpMethod::GET, '/files/{name}', 'FileHandler');

		$this->assertNull($route->match('/files/a/b'));
	}
}
