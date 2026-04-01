<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\ServiceLoader;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Sakoo\Framework\Core\Clock\Clock;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;
use Sakoo\Framework\Core\Logger\FileLogger;
use Sakoo\Framework\Core\Markup\Markdown;
use Sakoo\Framework\Core\Markup\Markup;
use Sakoo\Framework\Core\Profiler\Profiler;
use Sakoo\Framework\Core\Profiler\ProfilerInterface;
use Sakoo\Framework\Core\Str\Str;
use Sakoo\Framework\Core\Str\Stringable;

/**
 * Core service loader that registers the primary framework bindings.
 *
 * Wires the essential interfaces to their default implementations:
 *
 * - LoggerInterface  → FileLogger    (singleton — one logger per process)
 * - Markup           → Markdown      (transient — stateless renderer)
 * - ClockInterface   → Clock         (transient — PSR-20 clock)
 * - Stringable       → Str           (transient — fluent string wrapper)
 * - ProfilerInterface→ Profiler      (transient — millisecond profiler)
 * - ContainerInterface→ Container    (transient — self-reference for code that
 *                                     needs the container via its interface)
 *
 * Loaded by Kernel::run() as part of the default Loaders list. Application-level
 * service loaders may override these bindings by registering after MainLoader.
 */
class MainLoader extends ServiceLoader
{
	/**
	 * Registers the core framework interface-to-implementation bindings into $container.
	 */
	public function load(Container $container): void
	{
		$container->singleton(LoggerInterface::class, FileLogger::class);

		$container->bind(Markup::class, Markdown::class);
		$container->bind(ClockInterface::class, Clock::class);
		$container->bind(Stringable::class, Str::class);
		$container->bind(ProfilerInterface::class, Profiler::class);
		$container->singleton(ContainerInterface::class, Container::class);
	}
}
