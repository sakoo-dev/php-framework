<?php

declare(strict_types=1);

use Sakoo\Framework\Core\ServiceLoader\HttpServiceLoader;
use Sakoo\Framework\Core\ServiceLoader\MainLoader;
use Sakoo\Framework\Core\ServiceLoader\VarDumpLoader;
use Sakoo\Framework\Core\ServiceLoader\WatcherLoader;

return [
	MainLoader::class,
	WatcherLoader::class,
	VarDumpLoader::class,
	HttpServiceLoader::class,
];
