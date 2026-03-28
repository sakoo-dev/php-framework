<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\Method;

use Sakoo\Framework\Core\Doc\Object\Class\ClassInterface;
use Sakoo\Framework\Core\Doc\Object\PHPDoc;
use Sakoo\Framework\Core\Doc\Object\PhpDoc\PhpDocObject;

/**
 * Contract for method value objects used by the documentation generator.
 *
 * Implemented by both MethodObject (backed by ReflectionMethod) and
 * VirtualMethodObject (parsed from [at-sign]method PHPDoc tags). Formatters depend only
 * on this interface so they can render both real and virtual methods with the same
 * traversal logic.
 *
 * Key responsibilities surfaced by the interface:
 * - Identity:    getName(), getClass()
 * - Visibility:  isPrivate(), isProtected(), isPublic(), isStatic()
 * - Classification: isConstructor(), isMagicMethod(), isStaticInstantiator(),
 *                   isFrameworkFunction(), shouldNotDocument()
 * - Signature:   getMethodReturnTypes(), getDefaultValues(), getDefaultValueTypes(),
 *                getModifiers()
 * - Docs:        getPhpDocs()
 */
interface MethodInterface
{
	/**
	 * Returns the ClassInterface that owns this method.
	 */
	public function getClass(): ClassInterface;

	/**
	 * Returns the method name as a plain string.
	 */
	public function getName(): string;

	/**
	 * Returns true when the method has private visibility.
	 */
	public function isPrivate(): bool;

	/**
	 * Returns true when the method has protected visibility.
	 */
	public function isProtected(): bool;

	/**
	 * Returns true when the method has public visibility.
	 */
	public function isPublic(): bool;

	/**
	 * Returns true when the method is declared static.
	 */
	public function isStatic(): bool;

	/**
	 * Returns true when the method is a constructor (__construct).
	 */
	public function isConstructor(): bool;

	/**
	 * Returns true when the method name starts with '__', indicating a PHP magic method.
	 */
	public function isMagicMethod(): bool;

	/**
	 * Returns a human-readable string of the method's return type(s), or null
	 * when no return type is declared.
	 */
	public function getMethodReturnTypes(): ?string;

	public function getRawDoc(): string;

	public function getPhpDocObject(): PhpDocObject;

	/**
	 * Returns the modifier names (e.g. ['public', 'static']) for this method.
	 *
	 * @return string[]
	 */
	public function getModifiers(): array;

	/**
	 * Returns true when the method is defined within the Sakoo framework namespace.
	 */
	public function isFrameworkFunction(): bool;

	/**
	 * Returns a comma-separated string of parameter variables (e.g. '$name, $value')
	 * suitable for use in call-site usage examples.
	 */
	public function getDefaultValues(): string;

	/**
	 * Returns a comma-separated string of typed parameter declarations
	 * (e.g. 'string $name, int $value') suitable for use in contract/signature examples.
	 */
	public function getDefaultValueTypes(): string;

	/**
	 * Returns true when this method should be excluded from generated documentation
	 * (private, carries DontDocument attribute, or is a non-constructor magic method).
	 */
	public function shouldNotDocument(): bool;

	/**
	 * Returns true when this method is a static named constructor that returns an
	 * instance of the owning class (self, static, or the class name).
	 */
	public function isStaticInstantiator(): bool;
}
