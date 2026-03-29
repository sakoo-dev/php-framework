<?php

declare(strict_types=1);

use Sakoo\Framework\Core\ServiceLoader\MainLoader;
use Sakoo\Framework\Core\ServiceLoader\VarDumpLoader;
use Sakoo\Framework\Core\ServiceLoader\WatcherLoader;
use System\ServiceLoader\AIServiceLoader;

return [
	MainLoader::class,
	WatcherLoader::class,
	VarDumpLoader::class,
	AIServiceLoader::class,
];
