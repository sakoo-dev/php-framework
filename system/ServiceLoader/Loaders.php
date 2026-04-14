<?php

declare(strict_types=1);

use Sakoo\Framework\Core\ServiceLoader\HttpServiceLoader as CoreHttpServiceLoader;
use Sakoo\Framework\Core\ServiceLoader\MainLoader;
use Sakoo\Framework\Core\ServiceLoader\VarDumpLoader;
use Sakoo\Framework\Core\ServiceLoader\WatcherLoader;
use System\ServiceLoader\AIServiceLoader;
use System\ServiceLoader\HttpServiceLoader;

return [
	MainLoader::class,
	WatcherLoader::class,
	VarDumpLoader::class,
	CoreHttpServiceLoader::class,
	HttpServiceLoader::class,
	AIServiceLoader::class,
];
