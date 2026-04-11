<?php

declare(strict_types=1);

use System\Middleware\ProfilerMiddleware;
use System\Middleware\RequestIdMiddleware;
use System\Middleware\XssProtectionMiddleware;

/*
 * Global HTTP middleware stack applied to every request.
 *
 * Order matters: first entry runs outermost (wraps everything else).
 * Add new middleware here — no need to touch the entry point files.
 */
return [
	ProfilerMiddleware::class,
	RequestIdMiddleware::class,
	XssProtectionMiddleware::class,
];
