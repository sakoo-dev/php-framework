<?php

declare(strict_types=1);

namespace System\Testing;

use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Kernel\Environment;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Kernel\Mode;
use System\Handler\ErrorHandler;
use System\Handler\ExceptionHandler;
use System\Path\Path;

trait Bootstrap
{
	public static function runKernel(): void
	{
		$envFile = File::open(Disk::Local, Path::getRootDir() . '/.env.testing');
		Env::load($envFile);

		$loaders = require Path::getSystemDir() . '/ServiceLoader/Loaders.php';
		$timeZone = Env::get('SERVER_TIME_ZONE', 'UTC');

		Kernel::prepare(Mode::Test, Environment::Debug)
			->setErrorHandler(new ErrorHandler())
			->setExceptionHandler(new ExceptionHandler())
			->setServiceLoaders($loaders)
			->setServerTimezone($timeZone)
			->run();
	}
}
