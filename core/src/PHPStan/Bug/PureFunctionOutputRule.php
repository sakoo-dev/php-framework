<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\PHPStan\Bug;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use Sakoo\Framework\Core\Doc\Attributes\DontDocument;

/**
 * PHPStan rule that flags discarded return values of known pure functions.
 *
 * Pure functions — functions with no observable side effects — only produce value
 * through their return value. Calling them as a statement (discarding the return)
 * is always a bug: the result of strlen(), trim(), array_map(), etc. is silently
 * thrown away, meaning the call has no effect whatsoever.
 *
 * This rule raises an error for every Expression node whose inner expression is a
 * FuncCall to a function listed in PURE_FUNCTIONS. The error is identified by the
 * rule signature constant so it can be selectively suppressed with
 * [at-sign]phpstan-ignore sakoo.bug.pureFunctionOutput when intentional.
 *
 * @implements Rule<Expression>
 */
#[DontDocument]
class PureFunctionOutputRule implements Rule
{
	private const PURE_FUNCTIONS = [
		'strlen', 'count', 'array_keys', 'array_values',
		'trim', 'substr', 'explode', 'implode', 'strtolower',
		'strtoupper', 'str_replace', 'json_encode', 'json_decode',
		'array_map', 'array_filter', 'in_array',
	];

	/** PHPStan error identifier used to reference or suppress this rule. */
	final public const string RULE_SIGNATURE = 'sakoo.bug.pureFunctionOutput';

	/**
	 * Returns the AST node type this rule inspects.
	 */
	public function getNodeType(): string
	{
		return Expression::class;
	}

	/**
	 * Checks whether the expression statement is a bare call to a pure function.
	 * Returns a single error when the function name is in the known-pure list,
	 * or an empty array when no violation is detected.
	 *
	 * @return list<RuleError>
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->expr instanceof FuncCall) {
			return [];
		}

		$funcCall = $node->expr;

		if (!$funcCall->name instanceof Node\Name) {
			return [];
		}

		$functionName = strtolower((string) $funcCall->name);

		if (in_array($functionName, self::PURE_FUNCTIONS, true)) {
			$message = "The return value of '{$functionName}' should not be ignored as it has no side effects.";

			return [RuleErrorBuilder::message($message)->identifier(self::RULE_SIGNATURE)->build()];
		}

		return [];
	}
}
