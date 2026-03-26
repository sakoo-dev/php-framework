<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\ServiceLoader;

use Sakoo\Framework\Core\Container\Container;

/**
 * Base class for all service loader definitions in the framework.
 *
 * A ServiceLoader is responsible for registering a cohesive group of bindings and
 * singletons into the container during the kernel boot sequence. Each module or
 * infrastructure concern (logging, caching, HTTP, database, etc.) should provide
 * its own ServiceLoader subclass so registrations remain co-located with the code
 * they configure and are easy to enable or disable per environment.
 *
 * ServiceLoaders are registered on the Kernel via Kernel::setServiceLoaders() and
 * are invoked once during Kernel::run() when no container cache is present. When
 * a cache exists, loadCache() is called instead and ServiceLoaders are bypassed
 * entirely for that boot cycle, making the cache path reflection-free.
 *
 * Concrete subclasses must implement load() and must not produce side-effects
 * beyond registering container bindings — no I/O, no network calls, no heavy
 * initialisation. Expensive initialisation belongs inside the factory callables
 * passed to bind() or singleton(), deferred until first resolution.
 */
abstract class ServiceLoader
{
	/**
	 * Registers bindings and singletons into $container for the services owned by
	 * this loader. Called once per boot cycle when no container cache is present.
	 */
	abstract public function load(Container $container): void;
}
