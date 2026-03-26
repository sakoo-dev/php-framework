<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object;

use Sakoo\Framework\Core\Doc\Attributes\DontDocument;
use Sakoo\Framework\Core\Exception\Exception;
use Sakoo\Framework\Core\Regex\Regex;

/**
 * Reflection-backed value object representing a single PHP class for documentation.
 *
 * Wraps a ReflectionClass and exposes the information the documentation generator
 * needs: the class short name, namespace, public/protected methods (as MethodObject
 * instances), virtual methods parsed from @method PHPDoc tags (as VirtualMethodObject
 * instances), and metadata flags (isException, isInstantiable, isIllegal).
 *
 * isIllegal() determines whether the class should be excluded from generated docs:
 * it returns true for classes carrying the DontDocument attribute, traits, abstracts,
 * and interfaces — leaving only concrete, documentable classes in the output.
 *
 * getPhpDocs() parses the class-level doc comment into trimmed lines for use by
 * formatters, and getVirtualMethods() extracts @method tag lines from those docs
 * and attempts to parse each one into a VirtualMethodObject.
 */
readonly class ClassObject
{
	/**
	 * @phpstan-ignore missingType.generics
	 */
	public function __construct(private \ReflectionClass $class) {}

	/**
	 * Returns all public and protected methods declared in the Sakoo framework
	 * namespace as MethodObject instances, skipping inherited non-framework methods.
	 *
	 * @return MethodObject[]
	 */
	public function getMethods(): array
	{
		$data = [];
		$methods = $this->class->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);

		foreach ($methods as $method) {
			$method = new MethodObject($this, $method);

			if ($method->isFrameworkFunction()) {
				$data[] = $method;
			}
		}

		return $data;
	}

	/**
	 * Returns the fully-qualified namespace name of the class (excluding the class
	 * name itself), used to group classes into NamespaceObject bags.
	 */
	public function getNamespace(): string
	{
		return $this->class->getNamespaceName();
	}

	/**
	 * Returns true when this class should be excluded from documentation.
	 *
	 * A class is illegal when it carries the DontDocument attribute, is a trait,
	 * is abstract, or is an interface.
	 */
	public function isIllegal(): bool
	{
		$dontDocument = !empty($this->class->getAttributes(DontDocument::class));

		return $dontDocument || $this->class->isTrait() || $this->class->isAbstract() || $this->class->isInterface();
	}

	/**
	 * Returns true when the class can be instantiated with new (not abstract, not interface).
	 */
	public function isInstantiable(): bool
	{
		return $this->class->isInstantiable();
	}

	/**
	 * Returns true when the class is a subclass of the framework base Exception,
	 * used by formatters to apply the 🟥 icon.
	 */
	public function isException(): bool
	{
		return $this->class->isSubclassOf(Exception::class);
	}

	/**
	 * Returns the unqualified short class name (without namespace prefix).
	 */
	public function getName(): string
	{
		return $this->class->getShortName();
	}

	/**
	 * Parses the class-level PHPDoc block and returns its content as an ordered
	 * array of trimmed lines. Returns an empty array when no doc comment exists.
	 *
	 * @return string[]
	 */
	public function getPhpDocs(): array
	{
		$phpDoc = $this->class->getDocComment();

		if (!$phpDoc) {
			return [];
		}

		$match = (new Regex())
			->startsWith('/**')
			->add('([\s\S]+)')
			->endsWith('*/')
			->match($phpDoc);

		$lines = explode("\n", $match ? $match[1] : '');
		$result = [];

		foreach ($lines as $line) {
			$result[] = trim($line, "/* \t\r\n");
		}

		return $result;
	}

	/**
	 * Parses @method tag lines from the class-level PHPDoc and returns them as
	 * VirtualMethodObject instances. Lines that fail to parse are silently skipped.
	 *
	 * @return VirtualMethodObject[]
	 */
	public function getVirtualMethods(): array
	{
		$phpDocs = $this->getPhpDocs();

		if (!$phpDocs) {
			return [];
		}

		$result = [];

		foreach ($phpDocs as $line) {
			if (str_starts_with($line, '@method ')) {
				try {
					$result[] = new VirtualMethodObject($this, $line);
				} catch (InvalidVirtualMethodDefinitionException $e) {
					continue;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns all interfaces implemented by this class as a map of
	 * interface-name → ReflectionClass, used by MethodObject to locate
	 * inherited PHPDoc comments from interface definitions.
	 *
	 * @return array<string, \ReflectionClass<object>>
	 */
	public function getInterfaces(): array
	{
		return $this->class->getInterfaces();
	}
}
