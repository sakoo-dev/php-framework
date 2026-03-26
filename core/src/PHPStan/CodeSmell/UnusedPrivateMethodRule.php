<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\PHPStan\CodeSmell;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Sakoo\Framework\Core\Doc\Attributes\DontDocument;

/**
 * PHPStan rule that reports private methods declared but never called within their class.
 *
 * Dead private methods accumulate as a codebase grows, adding confusion and maintenance
 * burden without contributing any behaviour. Because private methods are unreachable
 * from outside the declaring class, an uncalled private method is safe to delete.
 *
 * The rule inspects each InClassNode, collects all private non-magic method declarations,
 * then performs a recursive walk of every method body to gather all instance and static
 * method-call names. Any private method whose name does not appear in the called-names
 * set is reported as an error at its declaration line.
 *
 * Magic methods (__construct, __toString, etc.) are excluded because they are invoked
 * implicitly by the PHP engine and may never appear as explicit call sites.
 *
 * Errors are identified by RULE_SIGNATURE and can be suppressed per-site with
 * [at-sign]phpstan-ignore sakoo.codeSmell.unusedPrivateMethod.
 *
 * @implements Rule<InClassNode>
 */
#[DontDocument]
class UnusedPrivateMethodRule implements Rule
{
	/** PHPStan error identifier used to reference or suppress this rule. */
	final public const string RULE_SIGNATURE = 'sakoo.codeSmell.unusedPrivateMethod';

	/**
	 * Returns the AST node type this rule inspects.
	 */
	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	/**
	 * Finds all private non-magic methods in the class, collects all called method
	 * names from every method body, and reports a violation for each private method
	 * that is never called.
	 *
	 * @return list<RuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		/** @var InClassNode $node */
		$class = $node->getOriginalNode();
		$className = $node->getClassReflection()->getName();

		$privateMethods = [];
		$usedMethodNames = [];

		foreach ($class->getMethods() as $method) {
			$methodName = $method->name->toString();

			if ($method->isPrivate() && !$method->isMagic()) {
				$privateMethods[$methodName] = $method;
			}

			$usedMethodNames = array_merge($usedMethodNames, $this->collectCalledMethodNames($method));
		}

		$usedMethodNames = array_unique($usedMethodNames);
		$errors = [];

		foreach ($privateMethods as $name => $method) {
			if (!in_array($name, $usedMethodNames, true)) {
				$message = "Unused private method '{$name}' in class '{$className}' should be removed.";
				$errors[] = RuleErrorBuilder::message($message)->line($method->getLine())->identifier(self::RULE_SIGNATURE)->build();
			}
		}

		return $errors;
	}

	/**
	 * Recursively walks all sub-nodes of $node and returns the names of every
	 * instance method call (MethodCall) and static method call (StaticCall) found.
	 *
	 * @return array<string>
	 */
	private function collectCalledMethodNames(Node $node): array
	{
		$called = [];

		if ($node instanceof MethodCall || $node instanceof StaticCall) {
			if ($node->name instanceof Identifier) {
				$called[] = $node->name->toString();
			}
		}

		foreach ($node->getSubNodeNames() as $name) {
			$child = $node->{$name};

			if (is_array($child)) {
				foreach ($child as $c) {
					if ($c instanceof Node) {
						$called = array_merge($called, $this->collectCalledMethodNames($c));
					}
				}
			} elseif ($child instanceof Node) {
				$called = array_merge($called, $this->collectCalledMethodNames($child));
			}
		}

		return $called;
	}
}
