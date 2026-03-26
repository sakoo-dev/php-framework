<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\ServiceLoader;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\VarDump\Cli\CliDumper;
use Sakoo\Framework\Core\VarDump\Cli\CliFormatter;
use Sakoo\Framework\Core\VarDump\Dumper;
use Sakoo\Framework\Core\VarDump\Formatter;
use Sakoo\Framework\Core\VarDump\Http\HttpDumper;
use Sakoo\Framework\Core\VarDump\Http\HttpFormatter;

/**
 * Service loader that registers the appropriate VarDump driver for the current mode.
 *
 * Selects between two rendering stacks depending on whether the kernel is
 * running in HTTP mode or in a CLI/test context:
 *
 * - HTTP mode  → HttpDumper + HttpFormatter (HTML output into the response body)
 * - CLI/Test   → CliDumper  + CliFormatter  (ANSI-coloured output to the terminal)
 *
 * Both Dumper and Formatter are registered as singletons because there is only
 * ever one active output channel per process and constructing new formatters on
 * every dump() call would be wasteful.
 */
class VarDumpLoader extends ServiceLoader
{
	/**
	 * Registers the Dumper and Formatter singletons appropriate for the current
	 * kernel mode into $container.
	 */
	public function load(Container $container): void
	{
		if (kernel()->isInHttpMode()) {
			$container->singleton(Dumper::class, HttpDumper::class);
			$container->singleton(Formatter::class, HttpFormatter::class);

			return;
		}

		$container->singleton(Dumper::class, CliDumper::class);
		$container->singleton(Formatter::class, CliFormatter::class);
	}
}
