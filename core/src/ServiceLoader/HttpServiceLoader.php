<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\ServiceLoader;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Http\HttpFactory;
use Sakoo\Framework\Core\Http\Router\Router;
use System\Path\Path;

/**
 * Registers all HTTP module bindings into the container.
 *
 * Wires the PSR-17 factory interfaces to HttpFactory and registers the Router
 * as a singleton. After bindings are registered, discovers and loads route
 * files from all app modules by scanning app/{Module}/routes.php.
 */
class HttpServiceLoader extends ServiceLoader
{
	/**
	 * Registers HTTP factory, router, and transport bindings, then loads
	 * route definitions from all app modules.
	 */
	public function load(Container $container): void
	{
		$container->singleton(HttpFactory::class, HttpFactory::class);

		$container->bind(RequestFactoryInterface::class, fn () => resolve(HttpFactory::class));
		$container->bind(ResponseFactoryInterface::class, fn () => resolve(HttpFactory::class));
		$container->bind(ServerRequestFactoryInterface::class, fn () => resolve(HttpFactory::class));
		$container->bind(StreamFactoryInterface::class, fn () => resolve(HttpFactory::class));
		$container->bind(UploadedFileFactoryInterface::class, fn () => resolve(HttpFactory::class));
		$container->bind(UriFactoryInterface::class, fn () => resolve(HttpFactory::class));

		$container->singleton(Router::class, Router::class);
	}

	/**
	 * Scans all subdirectories of $appDir for routes.php files and invokes
	 * each with the Router instance. Called after the container is fully
	 * populated so handlers can be resolved.
	 */
	public static function loadRoutes(Router $router): void
	{
		$pattern = Path::getAppDir() . '/*/routes.php';

		foreach (glob($pattern) ?: [] as $routeFile) {
			$registrar = require $routeFile;

			if (is_callable($registrar)) {
				$registrar($router);
			}
		}
	}
}
