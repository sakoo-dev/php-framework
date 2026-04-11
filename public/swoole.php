<?php

declare(strict_types=1);

use Sakoo\Framework\Core\Http\Middleware\MiddlewarePipeline;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Router\Router;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Transport\Swoole\SwooleResponseEmitter;
use Sakoo\Framework\Core\Http\Transport\Swoole\SwooleTransportRequest;
use Sakoo\Framework\Core\ServiceLoader\HttpServiceLoader;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;
use System\Path\Path;

/** @var Router $router */
$router = resolve(Router::class);

$appDir = Path::getAppDir();

if ($appDir) {
	HttpServiceLoader::loadRoutes($router, $appDir);
}

/** @var array<class-string> $globalMiddleware */
$globalMiddleware = require Path::getSystemDir() . '/Middleware/Middlewares.php';

$server = new Server('0.0.0.0', 9501);

$server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($globalMiddleware): void {
	try {
		$transportRequest = new SwooleTransportRequest($swooleRequest);
		$psrRequest = $transportRequest->toPsrRequest();

		/** @var Router $router */
		$router = resolve(Router::class);

		$pipeline = new MiddlewarePipeline($router, $globalMiddleware);
		$psrResponse = $pipeline->handle($psrRequest);
	} catch (Throwable $e) {
		$psrResponse = new Response(500, '', body: Stream::createFromString($e->getMessage()));
	}

	$emitter = new SwooleResponseEmitter($swooleResponse);
	$emitter->emit($psrResponse);
});

$server->start();
