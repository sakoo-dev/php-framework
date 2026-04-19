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
use Sakoo\Framework\Core\Http\Client\HttpClient;
use Sakoo\Framework\Core\Http\Client\HttpClientInterface;
use Sakoo\Framework\Core\Http\Client\HttpDriverInterface;
use Sakoo\Framework\Core\Http\Client\StreamHttpDriver;
use Sakoo\Framework\Core\Http\Client\SwooleHttpDriver;
use Sakoo\Framework\Core\Http\HttpFactory;
use Sakoo\Framework\Core\Http\Router\Router;
use System\Path\Path;

/**
 * Registers all HTTP module bindings into the container.
 *
 * Wires the PSR-17 factory interfaces to HttpFactory, registers the Router as
 * a singleton, and binds the HttpClient with the correct driver based on SAPI:
 * SwooleHttpDriver under CLI (Swoole server) and StreamHttpDriver under FPM.
 */
class HttpServiceLoader extends ServiceLoader
{
	/**
	 * Registers HTTP factory, router, transport, and outbound client bindings.
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

		$container->bind(HttpDriverInterface::class, function (): HttpDriverInterface {
			$httpFactory = resolve(HttpFactory::class);

			return PHP_SAPI === 'cli' ? new SwooleHttpDriver($httpFactory) : new StreamHttpDriver($httpFactory);
		});

		$container->bind(HttpClientInterface::class, HttpClient::class);
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
