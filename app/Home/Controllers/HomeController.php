<?php

declare(strict_types=1);

namespace App\Home\Controllers;

use Sakoo\Framework\Core\Http\Controller;
use Sakoo\Framework\Core\Http\HttpRequest;
use Sakoo\Framework\Core\Http\HttpResponse;

class HomeController extends Controller
{
	public function home(HttpRequest $request): HttpResponse
	{
		return $this->json([
			'name' => 'Sakoo Framework',
			'message' => 'Welcome to Sakoo',
		]);
	}

	public function health(HttpRequest $request): HttpResponse
	{
		return $this->json(['status' => 'ok']);
	}
}
