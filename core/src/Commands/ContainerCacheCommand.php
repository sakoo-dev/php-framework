<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;

class ContainerCacheCommand extends Command
{
	public function __construct(private readonly ContainerInterface $container) {}

	public static function getName(): string
	{
		return 'container:cache';
	}

	public static function getDescription(): string
	{
		return 'Creates container cache for better performance';
	}

	public function run(Input $input, Output $output): int
	{
		if ($input->hasOption('clear')) {
			$this->container->flushCache();

			$output->success('Container cache cleared successfully.');

			return Output::SUCCESS;
		}

		$this->container->dumpCache();

		$output->success('Container cache created successfully.');

		return Output::SUCCESS;
	}
}
