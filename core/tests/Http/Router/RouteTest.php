<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Router\HttpMethod;
use Sakoo\Framework\Core\Http\Router\Route;
use Sakoo\Framework\Core\Tests\TestCase;

final class RouteTest extends TestCase
{
	#[Test]
	public function it_matches_exact_path(): void
	{
		$route = new Route(HttpMethod::GET, '/users', 'Handler');

		$this->assertSame([], $route->match('/users'));
	}

	#[Test]
	public function it_returns_null_on_non_match(): void
	{
		$route = new Route(HttpMethod::GET, '/users', 'Handler');

		$this->assertNull($route->match('/posts'));
	}

	#[Test]
	public function it_captures_named_parameters(): void
	{
		$route = new Route(HttpMethod::GET, '/users/{id}', 'Handler');
		$params = $route->match('/users/42');

		$this->assertSame(['id' => '42'], $params);
	}

	#[Test]
	public function it_captures_multiple_parameters(): void
	{
		$route = new Route(HttpMethod::GET, '/users/{userId}/posts/{postId}', 'Handler');
		$params = $route->match('/users/5/posts/99');

		$this->assertSame(['userId' => '5', 'postId' => '99'], $params);
	}

	#[Test]
	public function it_does_not_match_partial_path(): void
	{
		$route = new Route(HttpMethod::GET, '/users', 'Handler');

		$this->assertNull($route->match('/users/extra'));
	}

	#[Test]
	public function it_does_not_match_shorter_path(): void
	{
		$route = new Route(HttpMethod::GET, '/users/{id}', 'Handler');

		$this->assertNull($route->match('/users'));
	}
}
