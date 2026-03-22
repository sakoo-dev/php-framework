<?php

declare(strict_types=1);

use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Kernel\Environment;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Kernel\Mode;
use System\Handler\ErrorHandler;
use System\Handler\ExceptionHandler;
use System\Path\Path;

require_once __DIR__ . '/../vendor/autoload.php';

$envFile = File::open(Disk::Local, Path::getRootDir() . '/.env');
Env::load($envFile);

$loaders = require_once Path::getSystemDir() . '/ServiceLoader/Loaders.php';
$timeZone = Env::get('SERVER_TIME_ZONE', 'UTC');
$environment = Env::get('APP_DEBUG', true) ? Environment::Debug : Environment::Production;

Kernel::prepare(Mode::HTTP, $environment)
	->setErrorHandler(new ErrorHandler())
	->setExceptionHandler(new ExceptionHandler())
	->setServiceLoaders($loaders)
	->setServerTimezone($timeZone)
	->run();

require_once 'server.php';
