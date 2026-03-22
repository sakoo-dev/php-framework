<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container;

use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotInstantiableException;
use Sakoo\Framework\Core\Container\Exceptions\ContainerNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\TypeMismatchException;
use Sakoo\Framework\Core\Container\Parameter\ParameterSet;

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
	 * @throws \Throwable
	 * @throws ContainerNotFoundException
	 */
	public function get(string $id): object
	{
		throwUnless($this->has($id), new ContainerNotFoundException());

		return $this->resolve($id);
	}

	public function has(string $id): bool
	{
		return isset($this->singletons[$id]) || isset($this->bindings[$id]);
	}

	/**
	 * @throws \Throwable
	 * @throws TypeMismatchException
	 */
	public function bind(string $interface, callable|object|string $factory): void
	{
		$this->checkMismatchType($interface, $factory);
		$this->bindings[$interface] = $factory;
	}

	/**
	 * @throws \Throwable
	 * @throws TypeMismatchException
	 */
	public function singleton(string $interface, callable|object|string $factory): void
	{
		$this->checkMismatchType($interface, $factory);
		$this->singletons[$interface] = $factory;
	}

	/**
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

	public function clear(): void
	{
		$this->instances = [];
		$this->singletons = [];
		$this->bindings = [];
		$this->flushCache();
	}

	/**
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
	 * @param array<mixed> $params
	 */
	private function shouldAutowire(array $params): bool
	{
		return empty($params);
	}

	private function canAutowire(?\ReflectionMethod $constructor): bool
	{
		return !is_null($constructor) && $constructor->getNumberOfParameters() > 0;
	}
}
