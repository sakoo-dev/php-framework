<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\Method;

use Sakoo\Framework\Core\Doc\Attributes\DontDocument;
use Sakoo\Framework\Core\Doc\Object\Class\ClassObject;
use Sakoo\Framework\Core\Doc\Object\Parameter\ParameterObject;
use Sakoo\Framework\Core\Doc\Object\Parameter\TypeObject;
use Sakoo\Framework\Core\Doc\Object\PhpDoc\PhpDocObject;

/**
 * Reflection-backed value object representing a real PHP method for documentation.
 *
 * Wraps a ReflectionMethod and a parent ClassObject, implementing MethodInterface
 * so the documentation formatters can treat it identically to VirtualMethodObject.
 *
 * getPhpDocs() first checks the method's own doc comment; if absent it looks for
 * the same method on any interface implemented by the owning class, enabling
 * interface-level docs to propagate automatically to concrete implementations.
 *
 * shouldNotDocument() excludes private methods, methods carrying the DontDocument
 * attribute, and non-constructor magic methods (__toString, __get, etc.).
 *
 * isFrameworkFunction() guards against documenting inherited methods from third-party
 * dependencies by checking that the declaring class belongs to the Sakoo namespace.
 *
 * isStaticInstantiator() identifies static named constructors (e.g. Money::of())
 * so formatters can render them as "How to use the Class" instantiation snippets
 * rather than regular method contracts.
 */
readonly class MethodObject implements MethodInterface
{
	public function __construct(private ClassObject $classObject, private \ReflectionMethod $method) {}

	/**
	 * Returns the ClassObject that owns this method.
	 */
	public function getClass(): ClassObject
	{
		return $this->classObject;
	}

	/**
	 * Returns all parameters of this method as an ordered list of ParameterObject instances.
	 *
	 * @return ParameterObject[]
	 */
	public function getMethodParameters(): array
	{
		$parameters = [];

		foreach ($this->method->getParameters() as $parameter) {
			$parameters[] = new ParameterObject($parameter);
		}

		return $parameters;
	}

	/**
	 * Returns the method name as a plain string.
	 */
	public function getName(): string
	{
		return $this->method->getName();
	}

	/**
	 * Returns true when the method has private visibility.
	 */
	public function isPrivate(): bool
	{
		return $this->method->isPrivate();
	}

	/**
	 * Returns true when the method has protected visibility.
	 */
	public function isProtected(): bool
	{
		return $this->method->isProtected();
	}

	/**
	 * Returns true when the method has public visibility.
	 */
	public function isPublic(): bool
	{
		return $this->method->isPublic();
	}

	/**
	 * Returns true when the method is declared static.
	 */
	public function isStatic(): bool
	{
		return $this->method->isStatic();
	}

	/**
	 * Returns true when the method is __construct.
	 */
	public function isConstructor(): bool
	{
		return $this->method->isConstructor();
	}

	/**
	 * Returns true when the method name begins with '__'.
	 */
	public function isMagicMethod(): bool
	{
		return str_starts_with($this->method->getName(), '__');
	}

	/**
	 * Returns the human-readable return type string, resolving union types and
	 * short class names via TypeObject. Returns an empty string when no return
	 * type is declared.
	 */
	public function getMethodReturnTypes(): string
	{
		/** @var null|\ReflectionIntersectionType|\ReflectionNamedType|\ReflectionUnionType $type */
		$type = $this->method->getReturnType();

		return (new TypeObject($type))->getName() ?? '';
	}

	public function getRawDoc(): string
	{
		return $this->method->getDocComment() ?: '';
	}

	public function getPhpDocObject(): PhpDocObject
	{
		return new PhpDocObject($this);
	}

	/**
	 * Returns the human-readable modifier names (e.g. ['public', 'static']) via
	 * PHP's Reflection::getModifierNames().
	 *
	 * @return string[]
	 */
	public function getModifiers(): array
	{
		return \Reflection::getModifierNames($this->method->getModifiers());
	}

	/**
	 * Returns true when the declaring class belongs to the Sakoo framework namespace,
	 * filtering out methods inherited from third-party libraries.
	 */
	public function isFrameworkFunction(): bool
	{
		return str_starts_with($this->method->class, 'Sakoo\Framework\Core');
	}

	/**
	 * Returns a comma-separated list of bare parameter variable names prefixed with '$'
	 * (e.g. '$name, $value'), used in call-site usage examples.
	 */
	public function getDefaultValues(): string
	{
		return implode(', ', array_map(fn (ParameterObject $item) => '$' . $item->getName(), $this->getMethodParameters()));
	}

	/**
	 * Returns a comma-separated list of typed parameter declarations
	 * (e.g. 'string $name, int $value'), used in method contract examples.
	 */
	public function getDefaultValueTypes(): string
	{
		return implode(', ', array_map(fn (ParameterObject $item) => $item->getType()->getName() . ' $' . $item->getName(), $this->getMethodParameters()));
	}

	/**
	 * Returns true when this method should be excluded from generated documentation:
	 * private visibility, DontDocument attribute, or a non-constructor magic method.
	 */
	public function shouldNotDocument(): bool
	{
		$hasAttribute = !empty($this->method->getAttributes(DontDocument::class));

		return $this->isPrivate() || $hasAttribute || ($this->isMagicMethod() && !$this->isConstructor());
	}

	/**
	 * Returns true when this is a public static method on a non-instantiable class
	 * that returns self, static, or the method's own name — identifying it as a
	 * static named constructor to be rendered as a "How to use the Class" snippet.
	 */
	public function isStaticInstantiator(): bool
	{
		return !$this->getClass()->isInstantiable()
			&& $this->method->isPublic()
			&& $this->method->isStatic()
			&& in_array($this->getMethodReturnTypes(), ['self', 'static', $this->method->getName()], true);
	}
}
