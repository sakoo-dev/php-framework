<?php

declare(strict_types=1);

use App\Home\Controllers\HomeController;
use Sakoo\Framework\Core\Http\Router\Router;

return function (Router $router): void {
	$router->get('/', [HomeController::class, 'home']);
	$router->get('/health', [HomeController::class, 'health']);
};
