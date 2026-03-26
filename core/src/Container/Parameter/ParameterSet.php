<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Parameter;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotInstantiableException;

/**
 * Resolves an ordered list of ReflectionParameters to a flat array of concrete values.
 *
 * Acts as a thin orchestrator over Parameter, iterating constructor parameters in
 * declaration order and delegating each one to a single Parameter instance. The
 * resulting array is ready to be spread into ReflectionClass::newInstanceArgs().
 */
readonly class ParameterSet
{
	public function __construct(private Container $container) {}

	/**
	 * Resolves every parameter in $parameters to a concrete value and returns them
	 * as an ordered list suitable for constructor injection.
	 *
	 * @param array<\ReflectionParameter> $parameters
	 *
	 * @return list<mixed>
	 *
	 * @throws \ReflectionException
	 * @throws ClassNotFoundException
	 * @throws ClassNotInstantiableException
	 * @throws \Throwable
	 */
	public function resolve(array $parameters): array
	{
		$dependencies = [];
		$parameterEntity = new Parameter($this->container);

		foreach ($parameters as $parameter) {
			$dependencies[] = $parameterEntity->resolve($parameter);
		}

		return $dependencies;
	}
}
