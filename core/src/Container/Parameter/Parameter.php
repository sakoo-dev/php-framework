<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Parameter;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotInstantiableException;
use Sakoo\Framework\Core\Container\Exceptions\UnresolvableParameterException;

/**
 * Resolves a single constructor parameter to a concrete value.
 *
 * When the parameter carries a non-built-in type hint, the container is asked to
 * resolve that type. When a default value is declared on the parameter, that default
 * is returned as-is. When neither condition holds, a safe zero-value is synthesised
 * from the parameter's type (empty string, 0, false, empty array, etc.), preventing
 * reflection errors on optional infrastructure parameters that have no registered
 * binding.
 */
readonly class Parameter
{
	public function __construct(private Container $container) {}

	/**
	 * Resolves $parameter to a usable value.
	 *
	 * Resolution priority:
	 * 1. Non-built-in typed parameters are resolved through the container.
	 * 2. Parameters with a declared default value return that default.
	 * 3. All other parameters receive a synthesised zero-value based on their type.
	 *
	 * @throws \Throwable
	 * @throws \ReflectionException
	 * @throws ClassNotInstantiableException
	 * @throws ClassNotFoundException
	 */
	public function resolve(\ReflectionParameter $parameter): mixed
	{
		$dependency = $parameter->getType();

		if ($this->canResolveType($dependency)) {
			return $this->container->resolve("$dependency");
		}

		if ($parameter->isDefaultValueAvailable()) {
			return $parameter->getDefaultValue();
		}

		throw new UnresolvableParameterException("Cannot resolve value of Parameter [$parameter->name]");
	}

	/**
	 * Returns true when $type is a non-null, non-built-in type that the container
	 * can resolve — i.e. a class or interface name. Returns false for built-in scalar
	 * types, null, and untyped parameters.
	 */
	private function canResolveType(?\ReflectionType $type): bool
	{
		if (is_null($type)) {
			return false;
		}

		if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
			return false;
		}

		return true;
	}
}
