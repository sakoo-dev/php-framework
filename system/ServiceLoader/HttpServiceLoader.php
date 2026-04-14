<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Http\Router\Router;
use Sakoo\Framework\Core\ServiceLoader\HttpServiceLoader as CoreHttpServiceLoader;
use System\Http\HttpKernel;
use System\Path\Path;

class HttpServiceLoader extends CoreHttpServiceLoader
{
	public function load(Container $container): void
	{
		parent::load($container);

		$container->singleton(HttpKernel::class, function () {
			$globalMiddleware = require Path::getSystemDir() . '/Middleware/Middlewares.php';

			return new HttpKernel(resolve(Router::class), $globalMiddleware);
		});
	}
}
