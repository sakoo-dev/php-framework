<?php

declare(strict_types=1);

use Sakoo\Framework\Core\Http\Middleware\MiddlewarePipeline;
use Sakoo\Framework\Core\Http\Router\Router;
use Sakoo\Framework\Core\Http\Transport\Fpm\FpmResponseEmitter;
use Sakoo\Framework\Core\Http\Transport\Fpm\FpmTransportRequest;

$transportRequest = FpmTransportRequest::fromGlobals();
$psrRequest = $transportRequest->toPsrRequest();

/** @var Router $router */
$router = resolve(Router::class);

$pipeline = new MiddlewarePipeline($router);
$response = $pipeline->handle($psrRequest);

$emitter = new FpmResponseEmitter();
$emitter->emit($response);
