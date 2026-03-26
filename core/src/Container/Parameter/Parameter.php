<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Parameter;

use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotInstantiableException;

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

		return $this->generateDefaultValue($dependency);
	}

	/**
	 * Synthesises a safe zero-value from a reflection type.
	 *
	 * Named built-in types are mapped to their natural zero: string → '', int/integer → 0,
	 * float/double → 0.0, bool/boolean → false, array → [], object/stdClass → new stdClass,
	 * callable/closure → a no-op closure. Union and intersection types are resolved by
	 * iterating their member types and returning the first non-null zero-value found.
	 * Returns null when no suitable zero-value can be determined.
	 */
	private function generateDefaultValue(?\ReflectionType $type): mixed
	{
		if (is_null($type)) {
			return null;
		}

		if ($type instanceof \ReflectionNamedType) {
			return match ($type->getName()) {
				'string' => '',
				'int', 'integer' => 0,
				'float', 'double' => 0.0,
				'bool', 'boolean' => false,
				'array' => [],
				'object', 'stdClass' => new \stdClass(),
				'callable', 'closure' => function () {},
				default => null,
			};
		}

		if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
			$types = $type->getTypes();

			foreach ($types as $subType) {
				$value = $this->generateDefaultValue($subType);

				if (null !== $value) {
					return $value;
				}
			}
		}

		return null;
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
