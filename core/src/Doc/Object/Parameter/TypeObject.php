<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\Parameter;

/**
 * Resolves a ReflectionType to a documentation-friendly short name string.
 *
 * PHP's reflection API returns three distinct type classes — ReflectionNamedType,
 * ReflectionUnionType, and ReflectionIntersectionType. This value object normalises
 * them into a single nullable string suitable for documentation output:
 *
 * - Null (untyped)          → null
 * - Built-in named type     → the type name as-is (e.g. 'string', 'int', 'array')
 * - Non-built-in named type → the short class name without namespace prefix
 * - Union type              → pipe-joined list of short names (e.g. 'string|int|Foo')
 * - Intersection type       → not explicitly handled; getName() returns null
 */
readonly class TypeObject
{
	public function __construct(private ?\ReflectionType $type) {}

	/**
	 * Returns the human-readable type name, or null when no type hint was declared
	 * or the type cannot be resolved to a short representation.
	 */
	public function getName(): ?string
	{
		if (is_null($this->type)) {
			return null;
		}

		if ($this->type instanceof \ReflectionUnionType) {
			return $this->getReflectionUnionTypeName($this->type);
		}

		if ($this->type instanceof \ReflectionNamedType && $this->type->isBuiltin()) {
			return $this->type->getName();
		}

		if ($this->type instanceof \ReflectionNamedType) {
			return $this->getShortClassName($this->type->getName());
		}

		return null;
	}

	/**
	 * Joins each member of a union type into a pipe-separated string, using the
	 * short class name for non-built-in types. Trailing pipes are stripped.
	 */
	public function getReflectionUnionTypeName(\ReflectionUnionType $type): string
	{
		$result = '';

		foreach ($type->getTypes() as $reflectionNamedType) {
			/** @var \ReflectionNamedType $reflectionNamedType */
			$result .= ($reflectionNamedType->isBuiltin() ? $reflectionNamedType : $this->getShortClassName($reflectionNamedType->getName())) . '|';
		}

		if (str_ends_with($result, '|')) {
			$result = substr($result, 0, -1);
		}

		return $result;
	}

	/**
	 * Strips the namespace prefix from a fully-qualified class name and returns
	 * only the short class name (the last segment after the last backslash).
	 */
	private function getShortClassName(string $fullClassName): string
	{
		$classNameParts = explode('\\', $fullClassName);

		return array_pop($classNameParts);
	}
}
