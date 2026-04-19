<?php

declare(strict_types=1);

use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Http\Transport\Swoole\SwooleResponseEmitter;
use Sakoo\Framework\Core\Http\Transport\Swoole\SwooleTransportRequest;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;
use System\Http\HttpKernel;
use System\Path\Path;

$cpuCount = swoole_cpu_num();

$server = new Server('0.0.0.0', 9501);

$logFile = File::open(Disk::Local, Path::getStorageDir() . '/swoole/request.log');
$logFile->create();

$server->set([
	'worker_num' => $cpuCount * 2,
	'reactor_num' => $cpuCount * 2,
	'task_worker_num' => $cpuCount,
	'max_request' => 10_000,
	'max_request_grace' => 200,
	'max_conn' => 4_096,
	'backlog' => 4_096,
	'open_tcp_nodelay' => true,
	'http_compression' => false,
	'log_level' => kernel()->isInDebugEnv() ? SWOOLE_LOG_DEBUG : SWOOLE_LOG_WARNING,
	'log_file' => $logFile->getPath(),
]);

$httpKernel = resolve(HttpKernel::class);

$server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($httpKernel): void {
	$transportRequest = new SwooleTransportRequest($swooleRequest);
	$psrRequest = $transportRequest->toPsrRequest();
	$psrResponse = $httpKernel->handle($psrRequest);
	$emitter = new SwooleResponseEmitter($swooleResponse);
	$emitter->emit($psrResponse);
});

$server->start();
