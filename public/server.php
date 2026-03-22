<?php

declare(strict_types=1);

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9501);

$server->set([
	'worker_num' => swoole_cpu_num() * 2,
	'daemonize' => false,
	'max_request' => 10000,
	'dispatch_mode' => 2,
	'debug_mode' => 0,
	'enable_coroutine' => true,
	'log_level' => \SWOOLE_LOG_TRACE,
]);

// Kernel-Level Components: Container -> Infra (Router, DB, Clients, ...)

/*
 * $app = Application::boot();
 * Contains App-Level (Per Request) components => Request, Response, Context, App-Level Container
 */

$server->on('request', function (Request $request, Response $response) {
	/*
	 * $app->handle($request, $response);
	 * destrois scoped container
	 * loads Router from Kernel-Level Container
	 */

	if ('/health' === $request->server['request_uri']) {
		$response->status(200);
		$response->end('OK');

		return;
	}

	$response->header('Content-Type', 'text/html; charset=utf-8');
	$response->status(200);
	$response->end('<h1>Hello World!</h1>');
});

$server->on('start', function (Server $server) {
	echo "Swoole HTTP server started at http://127.0.0.1:9501\n";
	echo 'Worker processes: ' . (swoole_cpu_num() * 2) . "\n";
});

$server->on('shutdown', function (Server $server) {
	echo "Swoole HTTP server shutdown at http://127.0.0.1:9501\n";
});

$server->start();
