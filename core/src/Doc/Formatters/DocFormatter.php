<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Formatters;

use Sakoo\Framework\Core\Doc\Object\Class\ClassInterface;
use Sakoo\Framework\Core\Doc\Object\Method\MethodInterface;
use Sakoo\Framework\Core\Doc\Object\Method\MethodObject;
use Sakoo\Framework\Core\Doc\Object\NamespaceObject;
use Sakoo\Framework\Core\Doc\Object\PhpDoc\PhpDocObject;

/**
 * Full API reference formatter for the documentation generator.
 *
 * Produces a Markdown document structured as:
 *
 *   # 📚 Documentation
 *   ## 📦 Namespace\Name
 *   ### 🟢/🟥 ClassName
 *   #### How to use the Class:   (constructor or static instantiator)
 *   ##### Contract / Usage       (regular methods)
 *
 * For each method the formatter emits:
 * - A "How to use the Class" block for constructors and static named constructors,
 *   showing the instantiation expression.
 * - A Contract block (full signature with modifiers and return type) and a Usage
 *   block (call-site expression with `$instance->method($args)` or `Class::method()`)
 *   for all other public/protected methods.
 * - Inline PHPDoc lines rendered as small text, with @throws lines rendered as
 *   callout blocks.
 *
 * Classes marked as exceptions are given a 🟥 icon; all others use 🟢.
 * Methods carrying the DontDocument attribute, private methods, and non-constructor
 * magic methods are silently skipped.
 *
 * TODO: add @throws label rendering for methods, Attribute support, helper function support.
 */
class DocFormatter extends Formatter
{
	/**
	 * Iterates all namespaces and their classes, renders each class's methods,
	 * and returns the complete Markdown API reference as a string.
	 *
	 * @param NamespaceObject[] $namespaces
	 */
	public function format(array $namespaces): string
	{
		$this->markup->h1('📚 Documentation');

		foreach ($namespaces as $namespace) {
			$this->markup->h2('📦 ' . $namespace->getName());
			$this->parseNamespace($namespace);
		}

		return (string) $this->markup;
	}

	/**
	 * Renders a single method: constructor/static-instantiator as a "How to use"
	 * snippet, and regular methods as a Contract + Usage pair.
	 */
	private function parseMethod(MethodInterface $method): void
	{
		$class = $method->getClass();
		$parameters = $method->getDefaultValueTypes();
		$parametersVars = $method->getDefaultValues();
		$modifiers = $method->getModifiers();
		$modifiers = implode(' ', $modifiers) . ($modifiers ? ' ' : '');

		if ($returnTypes = $method->getMethodReturnTypes()) {
			$returnTypes = ': ' . $returnTypes;
		}

		$instancePointer = '$' . lcfirst($class->getName());

		if ($method->isStaticInstantiator()) {
			$this->markup->h4('How to use the Class:');
			$this->markup->code("$instancePointer = " . $class->getName() . '::' . $method->getName() . "($parameters);", 'php');

			return;
		}

		if ($method->isConstructor()) {
			$this->markup->h4('How to use the Class:');
			$this->markup->code("$instancePointer = new " . $class->getName() . "($parameters);", 'php');

			return;
		}

		$this->markup->h3('- `' . $method->getName() . '` Function');
		$this->parsePHPDocs($method->getPhpDocObject());
		$code = '// --- Contract' . PHP_EOL;
		$code .= "{$modifiers}function " . $method->getName() . "($parameters)$returnTypes" . PHP_EOL;
		$code .= '// --- Usage' . PHP_EOL;
		$code .= ($method->isStatic() ? $class->getName() . '::' : "$instancePointer->") . $method->getName() . "($parametersVars);";

		$this->markup->code($code, 'php');
	}

	/**
	 * Iterates all virtual and real methods of $class, skipping undocumentable ones,
	 * and renders each via parseMethod() followed by its PHPDoc text.
	 */
	private function parseClass(ClassInterface $class): void
	{
		/** @var MethodInterface[] $methods */
		$methods = array_merge($class->getVirtualMethods(), $class->getMethods());

		foreach ($methods as $method) {
			/** @var MethodObject $method */
			if ($method->shouldNotDocument()) {
				continue;
			}

			$this->parseMethod($method);
		}
	}

	/**
	 * Iterates all classes in $namespace, prepends an exception/regular icon, and
	 * delegates class rendering to parseClass().
	 */
	private function parseNamespace(NamespaceObject $namespace): void
	{
		foreach ($namespace->getClasses() as $class) {
			if ($class->shouldNotDocument()) {
				continue;
			}

			$icon = $class->isException() ? '🟥' : '🟢';
			$this->markup->h3($icon . ' ' . $class->getName());
			$this->parsePHPDocs($class->getPhpDocObject());
			$this->parseClass($class);
		}
	}

	/**
	 * Renders the PHPDoc lines for $method. Blank lines flush accumulated text as
	 * a small-text paragraph. @throws lines are rendered as callout blocks.
	 * Remaining text is concatenated into a paragraph flushed at the end.
	 */
	private function parsePHPDocs(PhpDocObject $phpDoc): void
	{
		$textBuffer = '';

		foreach ($phpDoc->getLines() as $line) {
			if ($line->isEmpty()) {
				if ($textBuffer) {
					$this->markup->tiny(htmlspecialchars($textBuffer));
					$textBuffer = '';
					$this->markup->br();
				}

				continue;
			}

			if ($line->isThrows()) {
				$this->markup->callout(htmlspecialchars((string) $line));
			} else {
				$textBuffer .= ((string) $line) . ' ';
			}
		}

		if ($textBuffer) {
			$this->markup->tiny(htmlspecialchars(trim($textBuffer)));
		}
	}
}
