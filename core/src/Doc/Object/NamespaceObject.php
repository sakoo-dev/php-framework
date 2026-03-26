<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object;

/**
 * Immutable value object grouping a namespace string with its ClassObject members.
 *
 * Acts as a bag that the Doc generator populates during source-file introspection,
 * grouping all documentable classes that share the same PHP namespace into one
 * unit for formatters to iterate over. The namespace name is used as the section
 * heading in generated documentation.
 */
readonly class NamespaceObject
{
	/**
	 * @param ClassObject[] $classes
	 */
	public function __construct(
		private string $namespace,
		private array $classes,
	) {}

	/**
	 * Returns the ClassObject instances belonging to this namespace.
	 *
	 * @return ClassObject[]
	 */
	public function getClasses(): array
	{
		return $this->classes;
	}

	/**
	 * Returns the fully-qualified namespace string (e.g. 'Sakoo\Framework\Core\Set').
	 */
	public function getName(): string
	{
		return $this->namespace;
	}
}
