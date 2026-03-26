<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object;

/**
 * Reflection-backed value object representing a single method parameter.
 *
 * Wraps a ReflectionParameter and exposes the two pieces of information
 * formatters need: the parameter name (for usage examples) and the type
 * (for contract signatures), delegating type resolution to TypeObject.
 */
readonly class ParameterObject
{
	public function __construct(private \ReflectionParameter $parameter) {}

	/**
	 * Returns the parameter name without the leading '$' sigil.
	 */
	public function getName(): string
	{
		return $this->parameter->getName();
	}

	/**
	 * Returns a TypeObject wrapping the parameter's declared type, or wrapping
	 * null when no type hint is present.
	 */
	public function getType(): TypeObject
	{
		/** @var null|\ReflectionIntersectionType|\ReflectionNamedType|\ReflectionUnionType $type */
		$type = $this->parameter->getType();

		return new TypeObject($type);
	}
}
