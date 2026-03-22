<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands\Watcher;

use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Watcher\Contracts\Event;
use Sakoo\Framework\Core\Watcher\WatcherActions;

class PhpBundler extends WatcherActions
{
	public function __construct(
		// @phpstan-ignore property.onlyWritten
		private readonly Input $input,
		private readonly Output $output,
	) {}

	public function fileModified(Event $event): void
	{
		parent::fileModified($event);

		$path = $event->getFile()->getPath();
		$this->output->block("$path changed at " . date('H:i:s'), Output::COLOR_GREEN);

		$this->makeLint($path);

		$event->getFile()->getLocker()->unlock();
		$this->output->block('Watching ...', Output::COLOR_CYAN);
	}

	private function makeLint(string $path): void
	{
		$vendor = Path::getVendorDir();
		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		exec("php $vendor/bin/php-cs-fixer fix $path --quiet");
	}
}
