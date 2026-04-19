<?php

declare(strict_types=1);

namespace System\ServiceLoader;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Http\Router\Router;
use Sakoo\Framework\Core\ServiceLoader\HttpServiceLoader as CoreHttpServiceLoader;
use System\Http\HttpKernel;
use System\Middleware\ProfilerMiddleware;
use System\Middleware\RequestIdMiddleware;
use System\Middleware\XssProtectionMiddleware;
use System\Path\Path;

/**
 * System-level HTTP service loader.
 *
 * Extends the core HttpServiceLoader to register system middleware singletons
 * and bind the HttpKernel with the global middleware stack assembled from
 * Middlewares.php. All core HTTP bindings (PSR-17 factories, Router, HttpClient)
 * are registered by the parent before the system-specific additions below.
 */
class HttpServiceLoader extends CoreHttpServiceLoader
{
	/**
	 * Registers system middleware singletons and wires the HttpKernel singleton
	 * with the global middleware stack loaded from Middlewares.php.
	 */
	public function load(Container $container): void
	{
		parent::load($container);

		$container->singleton(ProfilerMiddleware::class, ProfilerMiddleware::class);
		$container->singleton(RequestIdMiddleware::class, RequestIdMiddleware::class);
		$container->singleton(XssProtectionMiddleware::class, XssProtectionMiddleware::class);

		$container->singleton(HttpKernel::class, function (): HttpKernel {
			$globalMiddleware = require Path::getSystemDir() . '/Middleware/Middlewares.php';

			return new HttpKernel(resolve(Router::class), $globalMiddleware);
		});
	}
}
