<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;

/**
 * Console command for managing the container binding cache.
 *
 * Provides two operations selected by the presence of the --clear option:
 *
 * - Default (no --clear): serialises the current container bindings and
 *   singletons to a PHP cache file via ContainerInterface::dumpCache(), allowing
 *   subsequent boots to skip all reflection-based autowiring.
 * - --clear: deletes the existing cache file via ContainerInterface::flushCache(),
 *   forcing the next boot to re-run all ServiceLoaders and rebuild the cache.
 *
 * The container is injected via the constructor so the command operates on the
 * same ContainerInterface instance that was used during the current boot cycle.
 */
class ContainerCacheCommand extends Command
{
	public function __construct(private readonly ContainerInterface $container) {}

	/**
	 * Returns the CLI argument name 'container:cache' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'container:cache';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'Creates container cache for better performance';
	}

	/**
	 * Flushes the cache when --clear is present, otherwise dumps a fresh cache.
	 * Prints a success message and returns Output::SUCCESS in both cases.
	 */
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
