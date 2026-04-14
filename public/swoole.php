<?php

declare(strict_types=1);

use Sakoo\Framework\Core\Http\Transport\Swoole\SwooleResponseEmitter;
use Sakoo\Framework\Core\Http\Transport\Swoole\SwooleTransportRequest;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;
use System\Http\HttpKernel;

$server = new Server('0.0.0.0', 9501);
$httpKernel = resolve(HttpKernel::class);

$server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse) use ($httpKernel): void {
	$transportRequest = new SwooleTransportRequest($swooleRequest);
	$psrRequest = $transportRequest->toPsrRequest();
	$psrResponse = $httpKernel->handle($psrRequest);
	$emitter = new SwooleResponseEmitter($swooleResponse);
	$emitter->emit($psrResponse);
});

$server->start();
