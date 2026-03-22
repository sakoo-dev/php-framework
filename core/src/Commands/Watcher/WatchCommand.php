<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands\Watcher;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Watcher\Watcher;

class WatchCommand extends Command
{
	public function __construct(private readonly Watcher $watcher) {}

	public static function getName(): string
	{
		return 'watch';
	}

	public static function getDescription(): string
	{
		return 'Run the file Watcher';
	}

	public function run(Input $input, Output $output): int
	{
		$this->watcher->watch(Path::getProjectPHPFiles(), new PhpBundler($input, $output));
		$output->block('Watching ...', Output::COLOR_CYAN);
		$this->watcher->run();

		return Output::SUCCESS;
	}
}
