<?php

declare(strict_types=1);

use Sakoo\Framework\Core\Http\Transport\Fpm\FpmResponseEmitter;
use Sakoo\Framework\Core\Http\Transport\Fpm\FpmTransportRequest;
use System\Http\HttpKernel;

$transportRequest = FpmTransportRequest::fromGlobals();
$psrRequest = $transportRequest->toPsrRequest();
$response = resolve(HttpKernel::class)->handle($psrRequest);
$emitter = new FpmResponseEmitter();
$emitter->emit($response);
