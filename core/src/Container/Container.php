<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container;

use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotInstantiableException;
use Sakoo\Framework\Core\Container\Exceptions\ContainerNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\TypeMismatchException;
use Sakoo\Framework\Core\Container\Parameter\ParameterSet;

/**
 * PSR-11-compliant inversion-of-control container with autowiring and cache support.
 *
 * The Container manages three distinct resolution strategies:
 *
 * - Transient bindings (bind()): a fresh object is produced on every call to resolve().
 * - Singleton bindings (singleton()): the object is created once on first resolution
 *   and the same instance is returned on every subsequent call.
 * - Autowiring (new() / resolve() fallback): when no binding is registered, the
 *   container inspects the class constructor via reflection, recursively resolves all
 *   typed dependencies from itself, and synthesises zero-values for unresolvable
 *   primitive parameters.
 *
 * Factories may be provided as a class-name string, a pre-built object, or a callable
 * that returns an object. When registering a concrete class or object against an
 * interface, the container verifies that the implementation actually satisfies the
 * interface and throws TypeMismatchException otherwise.
 *
 * The Cacheable trait adds PHP-file-based persistence of the binding maps, allowing
 * production boots to skip all reflection overhead entirely.
 *
 * An optional $cachePath constructor argument enables caching; when omitted the
 * container operates purely in-memory.
 */
class Container implements ContainerInterface
{
	use Cacheable;

	/** @var array<object> */
	private array $instances = [];
	/** @var array<callable|object|string> */
	private array $singletons = [];
	/** @var array<callable|object|string> */
	private array $bindings = [];

	public function __construct(private readonly ?string $cachePath = null) {}

	/**
	 * Returns the object bound to $id, resolving it through the appropriate strategy.
	 * Throws ContainerNotFoundException when $id has no registered binding.
	 *
	 * @throws \Throwable
	 * @throws ContainerNotFoundException
	 */
	public function get(string $id): object
	{
		throwUnless($this->has($id), new ContainerNotFoundException());

		return $this->resolve($id);
	}

	/**
	 * Returns true when $id has a singleton or transient binding registered,
	 * false otherwise. Does not account for autowirable classes.
	 */
	public function has(string $id): bool
	{
		return isset($this->singletons[$id]) || isset($this->bindings[$id]);
	}

	/**
	 * Registers $factory as a transient binding for $interface. A new object is
	 * produced on every resolution. Throws TypeMismatchException when a non-callable
	 * factory does not implement the given interface.
	 *
	 * @throws \Throwable
	 * @throws TypeMismatchException
	 */
	public function bind(string $interface, callable|object|string $factory): void
	{
		$this->checkMismatchType($interface, $factory);
		$this->bindings[$interface] = $factory;
	}

	/**
	 * Registers $factory as a singleton binding for $interface. The object is
	 * instantiated once and cached for all subsequent resolutions. Throws
	 * TypeMismatchException when a non-callable factory does not implement $interface.
	 *
	 * @throws \Throwable
	 * @throws TypeMismatchException
	 */
	public function singleton(string $interface, callable|object|string $factory): void
	{
		$this->checkMismatchType($interface, $factory);
		$this->singletons[$interface] = $factory;
	}

	/**
	 * Resolves $interface to a concrete object.
	 *
	 * Resolution order: singleton registry → transient binding registry → direct
	 * autowired instantiation via new(). This method is the primary entry point for
	 * all dependency resolution inside the framework.
	 *
	 * @throws \ReflectionException
	 * @throws \Throwable
	 * @throws ClassNotInstantiableException
	 * @throws ClassNotFoundException
	 */
	public function resolve(string $interface): object
	{
		if (isset($this->singletons[$interface])) {
			return $this->resolveFromSingletons($interface);
		}

		if (isset($this->bindings[$interface])) {
			return $this->resolveFromBindings($interface);
		}

		return $this->new($interface);
	}

	/**
	 * Directly instantiates $class using reflection, bypassing the binding registry.
	 *
	 * When $params is empty and the constructor has typed parameters, the container
	 * autowires each dependency automatically. Explicit $params suppress autowiring
	 * entirely and are passed to the constructor as-is.
	 *
	 * @param array<mixed> $params
	 *
	 * @throws \ReflectionException
	 * @throws ClassNotFoundException
	 * @throws ClassNotInstantiableException
	 * @throws \Throwable
	 */
	public function new(string $class, array $params = []): object
	{
		throwUnless(class_exists($class), new ClassNotFoundException("$class cannot be found."));

		// @phpstan-ignore argument.type
		$reflector = new \ReflectionClass($class);

		throwUnless($reflector->isInstantiable(), new ClassNotInstantiableException());

		$constructor = $reflector->getConstructor();

		if ($this->shouldAutowire($params) && $this->canAutowire($constructor)) {
			/** @var \ReflectionMethod $constructor */
			$parameterSet = new ParameterSet($this);
			$params = $parameterSet->resolve($constructor->getParameters());
		}

		return $reflector->newInstanceArgs($params);
	}

	/**
	 * Resets all bindings, singleton registrations, cached instances, and the
	 * on-disk cache, returning the container to a pristine state.
	 */
	public function clear(): void
	{
		$this->instances = [];
		$this->singletons = [];
		$this->bindings = [];
		$this->flushCache();
	}

	/**
	 * Resolves a transient binding, always producing a fresh object via handleResolution().
	 *
	 * @throws \ReflectionException
	 * @throws ClassNotFoundException
	 * @throws ClassNotInstantiableException
	 * @throws \Throwable
	 */
	private function resolveFromBindings(string $interface): object
	{
		return $this->handleResolution($this->bindings[$interface]);
	}

	/**
	 * Resolves a singleton binding, creating the instance on first call and returning
	 * the cached instance on all subsequent calls.
	 *
	 * @throws \ReflectionException
	 * @throws ClassNotFoundException
	 * @throws ClassNotInstantiableException
	 * @throws \Throwable
	 */
	private function resolveFromSingletons(string $interface): object
	{
		if (!isset($this->instances[$interface])) {
			$this->instances[$interface] = $this->handleResolution($this->singletons[$interface]);
		}

		return $this->instances[$interface];
	}

	/**
	 * Dispatches a factory to the appropriate resolution strategy:
	 * callables are invoked, pre-built objects are returned directly, and
	 * class-name strings are instantiated via new().
	 *
	 * @throws \ReflectionException
	 * @throws ClassNotFoundException
	 * @throws ClassNotInstantiableException
	 * @throws \Throwable
	 */
	private function handleResolution(callable|object|string $factory): object
	{
		if (is_callable($factory)) {
			return (object) call_user_func($factory);
		}

		if (is_object($factory)) {
			return $factory;
		}

		return $this->new($factory);
	}

	/**
	 * Guards against registering a concrete type against an incompatible interface.
	 * Callables are exempt from this check because their return type cannot be
	 * statically verified at registration time.
	 *
	 * @throws TypeMismatchException
	 * @throws \Throwable
	 */
	private function checkMismatchType(string $interface, callable|object|string $factory): void
	{
		if (!is_callable($factory)) {
			throwIf(interface_exists($interface) && !is_subclass_of($factory, $interface), new TypeMismatchException());
		}
	}

	/**
	 * Returns true when no explicit params were provided, indicating that autowiring
	 * should be attempted for the constructor dependencies.
	 *
	 * @param array<mixed> $params
	 */
	private function shouldAutowire(array $params): bool
	{
		return empty($params);
	}

	/**
	 * Returns true when the constructor is non-null and has at least one parameter,
	 * meaning there is something for the autowirer to resolve.
	 */
	private function canAutowire(?\ReflectionMethod $constructor): bool
	{
		return !is_null($constructor) && $constructor->getNumberOfParameters() > 0;
	}
}
