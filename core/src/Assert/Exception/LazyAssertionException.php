<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Exception;

use Sakoo\Framework\Core\Doc\Attributes\DontDocument;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Aggregates all assertion failures collected during a LazyAssertion run.
 *
 * Instead of stopping at the first failure, LazyAssertion accumulates every
 * InvalidArgumentException keyed by chain name, then calls
 * LazyAssertionException::init() to bundle them into a single, numbered message.
 * This gives callers a complete picture of all validation errors in one throw.
 */
class LazyAssertionException extends Exception
{
	#[DontDocument]
	public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Constructs a LazyAssertionException from the full map of accumulated failures.
	 * Each failure is formatted as a numbered line prefixed by its chain name.
	 *
	 * @phpstan-param array<string,array<int,InvalidArgumentException>> $exceptions
	 */
	public static function init(array $exceptions): self
	{
		/**
		 * @phpstan-ignore staticClassAccess.privateMethod
		 */
		$message = static::getLazyMessage($exceptions);

		return new self($message);
	}

	/**
	 * Builds the numbered, multi-line message from the exception map.
	 * Format: "<n>) <chainName>: <message>" — one line per failure.
	 *
	 * @phpstan-param array<string,array<int,InvalidArgumentException>> $value
	 */
	private static function getLazyMessage(array $value): string
	{
		$result = 'The following assertions failed:' . PHP_EOL;
		$i = 1;

		foreach ($value as $chainName => $chain) {
			foreach ($chain as $message) {
				$result .= "$i) $chainName: {$message->getMessage()}" . PHP_EOL;
				++$i;
			}
		}

		return $result;
	}
}
