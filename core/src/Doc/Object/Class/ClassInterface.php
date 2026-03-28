<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\Class;

use Sakoo\Framework\Core\Doc\Object\Method\MethodObject;
use Sakoo\Framework\Core\Doc\Object\PhpDoc\PhpDocObject;

/**
 * Reflection-backed value object representing a single PHP class for documentation.
 *
 * Wraps a ReflectionClass and exposes the information the documentation generator
 * needs: the class short name, namespace, public/protected methods (as MethodObject
 * instances), virtual methods parsed from [at-sign]method PHPDoc tags (as VirtualMethodObject
 * instances), and metadata flags (isException, isInstantiable, isIllegal).
 *
 * isIllegal() determines whether the class should be excluded from generated docs:
 * it returns true for classes carrying the DontDocument attribute, traits, abstracts,
 * and interfaces — leaving only concrete, documentable classes in the output.
 *
 * getPhpDocs() parses the class-level doc comment into trimmed lines for use by
 * formatters, and getVirtualMethods() extracts [at-sign]method tag lines from those docs
 * and attempts to parse each one into a VirtualMethodObject.
 */
interface ClassInterface
{
	/**
	 * Returns all public and protected methods declared in the Sakoo framework
	 * namespace as MethodObject instances, skipping inherited non-framework methods.
	 *
	 * @return MethodObject[]
	 */
	public function getMethods(): array;

	/**
	 * Returns the fully-qualified namespace name of the class (excluding the class
	 * name itself), used to group classes into NamespaceObject bags.
	 */
	public function getNamespace(): string;

	/**
	 * Returns true when this class should be excluded from documentation.
	 *
	 * A class is illegal when it carries the DontDocument attribute, is a trait,
	 * is abstract, or is an interface.
	 */
	public function isIllegal(): bool;

	/**
	 * Returns true when the class can be instantiated with new (not abstract, not interface).
	 */
	public function isInstantiable(): bool;

	/**
	 * Returns true when the class is a subclass of the framework base Exception,
	 * used by formatters to apply the 🟥 icon.
	 */
	public function isException(): bool;

	/**
	 * Returns the unqualified short class name (without namespace prefix).
	 */
	public function getName(): string;

	public function getRawDoc(): string;

	public function getPhpDocObject(): PhpDocObject;

	/**
	 * Returns all interfaces implemented by this class as a map of
	 * interface-name → ReflectionClass, used by MethodObject to locate
	 * inherited PHPDoc comments from interface definitions.
	 *
	 * @return array<string, \ReflectionClass<object>>
	 */
	public function getInterfaces(): array;
}
