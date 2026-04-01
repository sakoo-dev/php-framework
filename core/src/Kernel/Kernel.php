<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;
use Sakoo\Framework\Core\Kernel\Exceptions\IlegalKernelDestroyException;
use Sakoo\Framework\Core\Kernel\Exceptions\KernelIsNotStartedException;
use Sakoo\Framework\Core\Kernel\Exceptions\KernelTwiceCallException;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Profiler\ProfilerInterface;
use Sakoo\Framework\Core\ServiceLoader\ServiceLoader;

/**
 * Process-scoped application kernel and bootstrap coordinator.
 *
 * The Kernel is the single authoritative owner of the container, profiler, mode,
 * and environment for the lifetime of one PHP process. It is a strict singleton:
 * prepare() must be called exactly once (further calls throw KernelTwiceCallException)
 * and getInstance() must only be called after prepare() and run() have completed
 * (earlier calls throw KernelIsNotStartedException).
 *
 * Boot sequence:
 * 1. prepare() — creates the singleton instance, capturing Mode and Environment.
 * 2. (optional setters) — configure timezone, error/exception handlers, service
 *    loaders, and replica ID before run() is called.
 * 3. run() — applies timezone and error settings, loads the helpers file,
 *    initialises the Container, populates it from a cache file when available or
 *    from the registered ServiceLoaders otherwise, then resolves the Profiler.
 *
 * After run() returns the kernel is fully operational and all application code
 * may call kernel(), container(), resolve(), and the other global helpers freely.
 *
 * Error and exception handlers are registered only when non-null values have been
 * provided via setErrorHandler() / setExceptionHandler(). Display errors are
 * enabled automatically in Test and Debug environments.
 *
 * In horizontal scaling scenarios each process replica should be assigned a unique
 * identifier via setReplicaId() so log correlation and distributed tracing remain
 * meaningful across instances.
 *
 * Test isolation: call destroy() between test suites to reset the singleton so
 * prepare() can be called again with fresh configuration. This prevents state
 * leakage across independent test classes running in the same PHP process.
 */
class Kernel
{
	private static ?Kernel $instance = null;

	private ProfilerInterface $profiler;
	private ContainerInterface $container;
	private string $replicaId = '';

	private string $serverTimezone = 'UTC';
	/** @var null|callable */
	private $errorHandler;
	/** @var null|callable */
	private $exceptionHandler;

	/** @var array<ServiceLoader> */
	private array $serviceLoaders = [];

	private function __construct(
		private readonly Mode $mode,
		private readonly Environment $environment,
	) {}

	/**
	 * Creates and stores the singleton Kernel instance with the given Mode and
	 * Environment. Must be called exactly once per process.
	 *
	 * @throws KernelTwiceCallException when called more than once in the same process
	 */
	public static function prepare(Mode $mode, Environment $environment): self
	{
		if (!is_null(self::$instance)) {
			throw new KernelTwiceCallException();
		}

		return self::$instance = new self($mode, $environment);
	}

	/**
	 * Returns the singleton Kernel instance.
	 *
	 * @throws KernelIsNotStartedException when called before prepare() and run() have completed
	 */
	public static function getInstance(): self
	{
		if (!self::$instance) {
			throw new KernelIsNotStartedException();
		}

		return self::$instance;
	}

	/**
	 * Resets the singleton instance to null, clearing all kernel state.
	 *
	 * This allows prepare() to be called again with fresh Mode and Environment
	 * values. The container, profiler, service loaders, and all other configuration
	 * are discarded. When a container has been initialised, it is fully cleared
	 * (cached instances, binding maps, and on-disk cache) before the reference is
	 * released to prevent leaked singletons holding resources.
	 *
	 * Intended exclusively for test teardown — calling this in production code
	 * will leave the application in a broken state where kernel(), container(),
	 * resolve(), and all other global helpers throw KernelIsNotStartedException.
	 *
	 * Safe to call when no kernel exists (idempotent on null).
	 *
	 * @throws \RuntimeException|\Throwable when called outside of Test mode to prevent
	 *                                      accidental destruction of a running production
	 *                                      or console kernel
	 */
	public static function destroy(): void
	{
		if (self::$instance && !self::$instance->isInTestMode()) {
			throw new IlegalKernelDestroyException();
		}

		if (self::$instance && isset(self::$instance->container)) {
			self::$instance->container->clear();
		}

		self::$instance = null;
	}

	/**
	 * Executes the full boot sequence:
	 * applies the configured timezone, registers error and exception handlers,
	 * enables display_errors in debug/test environments, loads the global helpers
	 * file, initialises the Container with the storage path as its cache directory,
	 * populates bindings from the cache (when available) or from ServiceLoaders,
	 * and finally resolves the ProfilerInterface from the container.
	 */
	public function run(): void
	{
		if ($this->serverTimezone) {
			date_default_timezone_set($this->serverTimezone);
		}

		if ($this->errorHandler) {
			set_error_handler($this->errorHandler);
		}

		if ($this->exceptionHandler) {
			set_exception_handler($this->exceptionHandler);
		}

		if ($this->isInTestMode() || $this->isInDebugEnv()) {
			$this->enableDisplayErrors();
		}

		require_once Path::getCoreDir() . '/helpers.php';

		$this->container = new Container(Path::getStorageDir());

		if ($this->container->cacheExists()) {
			$this->container->loadCache();
		} else {
			array_walk($this->serviceLoaders, fn ($serviceLoader) => (new $serviceLoader())->load($this->container));
		}

		$this->profiler = resolve(ProfilerInterface::class);
	}

	/**
	 * Returns the current runtime Mode (Test, Console, or HTTP).
	 */
	public function getMode(): Mode
	{
		return $this->mode;
	}

	/**
	 * Returns the current deployment Environment (Debug or Production).
	 */
	public function getEnvironment(): Environment
	{
		return $this->environment;
	}

	/**
	 * Returns the resolved ProfilerInterface instance. Only available after run().
	 */
	public function getProfiler(): ProfilerInterface
	{
		return $this->profiler;
	}

	/**
	 * Returns the ContainerInterface instance. Only available after run().
	 */
	public function getContainer(): ContainerInterface
	{
		return $this->container;
	}

	/**
	 * Returns the replica identifier assigned to this process instance, or an empty
	 * string when no replica ID has been configured.
	 */
	public function getReplicaId(): string
	{
		return $this->replicaId;
	}

	/**
	 * Registers a callable to be used as the PHP exception handler via
	 * set_exception_handler() during run(). Returns the same Kernel instance for
	 * fluent configuration chaining before run() is called.
	 */
	public function setExceptionHandler(callable $handler): static
	{
		$this->exceptionHandler = $handler;

		return $this;
	}

	/**
	 * Registers a callable to be used as the PHP error handler via
	 * set_error_handler() during run(). Returns the same Kernel instance for
	 * fluent configuration chaining before run() is called.
	 */
	public function setErrorHandler(callable $handler): static
	{
		$this->errorHandler = $handler;

		return $this;
	}

	/**
	 * Sets the server timezone applied via date_default_timezone_set() during run().
	 * Has no effect when called after run().
	 */
	public function setServerTimezone(string $timezone): static
	{
		$this->serverTimezone = $timezone;

		return $this;
	}

	/**
	 * Registers the list of ServiceLoader class names to be instantiated and invoked
	 * during run() when no container cache is present. Must be called before run().
	 *
	 * @param array<ServiceLoader> $serviceLoaders
	 */
	public function setServiceLoaders(array $serviceLoaders): static
	{
		$this->serviceLoaders = $serviceLoaders;

		return $this;
	}

	/**
	 * Assigns a unique identifier to this process replica. Used for log correlation
	 * and distributed tracing in horizontally scaled deployments.
	 */
	public function setReplicaId(string $replicaId): static
	{
		$this->replicaId = $replicaId;

		return $this;
	}

	/**
	 * Returns true when the kernel is running in Test mode.
	 */
	public function isInTestMode(): bool
	{
		return Mode::Test === $this->mode;
	}

	/**
	 * Returns true when the kernel is running in HTTP mode.
	 */
	public function isInHttpMode(): bool
	{
		return Mode::HTTP === $this->mode;
	}

	/**
	 * Returns true when the kernel is running in Console mode.
	 */
	public function isInConsoleMode(): bool
	{
		return Mode::Console === $this->mode;
	}

	/**
	 * Returns true when the kernel is configured for the Debug environment.
	 */
	public function isInDebugEnv(): bool
	{
		return Environment::Debug === $this->environment;
	}

	/**
	 * Returns true when the kernel is configured for the Production environment.
	 */
	public function isInProductionEnv(): bool
	{
		return Environment::Production === $this->environment;
	}

	/**
	 * Enables PHP's display_errors and display_startup_errors directives and sets
	 * error_reporting to E_ALL. Called automatically in Test and Debug environments
	 * during run() to surface errors visibly without requiring a log viewer.
	 */
	private function enableDisplayErrors(): void
	{
		ini_set('display_startup_errors', 1);
		ini_set('display_errors', 1);
		error_reporting(E_ALL);
	}
}
