<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Router\Stubs;

use Sakoo\Framework\Core\Http\Controller;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;

/**
 * Stub multi-action controller for Router tests.
 */
class UserController extends Controller
{
	public function index(HttpRequest $request): HttpResponse
	{
		return $this->json(['action' => 'index']);
	}

	public function show(HttpRequest $request): HttpResponse
	{
		return $this->json([
			'action' => 'show',
			'id' => $request->routeParam('id'),
		]);
	}

	public function store(HttpRequest $request): HttpResponse
	{
		return $this->created('/users/1', ['action' => 'store']);
	}

	public function update(HttpRequest $request): HttpResponse
	{
		return $this->json([
			'action' => 'update',
			'id' => $request->routeParam('id'),
		]);
	}

	public function destroy(HttpRequest $request): HttpResponse
	{
		return $this->noContent();
	}
}
