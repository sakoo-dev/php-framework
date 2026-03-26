<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert;

use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\Assert\Exception\LazyAssertionException;
use Sakoo\Framework\Core\Doc\Attributes\DontDocument;

/**
 * Deferred assertion runner that collects all failures before throwing.
 *
 * Unlike Assert, which throws on the first failed assertion, LazyAssertion
 * accumulates every InvalidArgumentException raised during its run and reports
 * them all at once when validate() is called. This is particularly useful for
 * form or DTO validation where the caller needs a complete list of errors rather
 * than stopping at the first one.
 *
 * Usage pattern:
 *   Assert::lazy()
 *       ->that($email, 'email')->string()->notEmpty()
 *       ->that($age, 'age')->int()->greater(0)
 *       ->validate();
 *
 * Calling that() switches the active "chain" — subsequent assertion method calls
 * operate on the value bound in the most recent that() call. Each chain is keyed
 * by its $chainName so the final exception message can identify which field
 * triggered which failure.
 *
 * The full set of proxied assertion methods mirrors those on Assert and is
 * documented in the @method annotations above the class declaration.
 *
 * @method LazyAssertion true(mixed $value, string $message = '')
 * @method LazyAssertion false(mixed $value, string $message = '')
 * @method LazyAssertion bool(mixed $value, string $message = '')
 * @method LazyAssertion notBool(mixed $value, string $message = '')
 * @method LazyAssertion callable(mixed $value, string $message = '')
 * @method LazyAssertion notCallable(mixed $value, string $message = '')
 * @method LazyAssertion dir(string $value, string $message = '')
 * @method LazyAssertion notDir(string $value, string $message = '')
 * @method LazyAssertion file(string $value, string $message = '')
 * @method LazyAssertion notFile(string $value, string $message = '')
 * @method LazyAssertion link(string $value, string $message = '')
 * @method LazyAssertion notLink(string $value, string $message = '')
 * @method LazyAssertion uploadedFile(string $value, string $message = '')
 * @method LazyAssertion notUploadedFile(string $value, string $message = '')
 * @method LazyAssertion executableFile(string $value, string $message = '')
 * @method LazyAssertion notExecutableFile(string $value, string $message = '')
 * @method LazyAssertion writableFile(string $value, string $message = '')
 * @method LazyAssertion notWritableFile(string $value, string $message = '')
 * @method LazyAssertion readableFile(string $value, string $message = '')
 * @method LazyAssertion notReadableFile(string $value, string $message = '')
 * @method LazyAssertion exists(string $value, string $message = '')
 * @method LazyAssertion notExists(string $value, string $message = '')
 * @method LazyAssertion length(string $value, int $length, string $message = '')
 * @method LazyAssertion count(array|\Countable $value, int $count, string $message = '')
 * @method LazyAssertion equals(mixed $value, mixed $expected, string $message = '')
 * @method LazyAssertion notEquals(mixed $value, mixed $expected, string $message = '')
 * @method LazyAssertion same(mixed $value, mixed $expected, string $message = '')
 * @method LazyAssertion notSame(mixed $value, mixed $expected, string $message = '')
 * @method LazyAssertion empty(mixed $value, string $message = '')
 * @method LazyAssertion notEmpty(mixed $value, string $message = '')
 * @method LazyAssertion null(mixed $value, string $message = '')
 * @method LazyAssertion notNull(mixed $value, string $message = '')
 * @method LazyAssertion numeric(mixed $value, string $message = '')
 * @method LazyAssertion notNumeric(mixed $value, string $message = '')
 * @method LazyAssertion finite(float $value, string $message = '')
 * @method LazyAssertion infinite(float $value, string $message = '')
 * @method LazyAssertion float(mixed $value, string $message = '')
 * @method LazyAssertion notFloat(mixed $value, string $message = '')
 * @method LazyAssertion int(mixed $value, string $message = '')
 * @method LazyAssertion notInt(mixed $value, string $message = '')
 * @method LazyAssertion greater(int $value, int $expected, string $message = '')
 * @method LazyAssertion greaterOrEquals(int $value, int $expected, string $message = '')
 * @method LazyAssertion lower(int $value, int $expected, string $message = '')
 * @method LazyAssertion lowerOrEquals(int $value, int $expected, string $message = '')
 * @method LazyAssertion object(mixed $value, string $message = '')
 * @method LazyAssertion notObject(mixed $value, string $message = '')
 * @method LazyAssertion instanceOf(mixed $value, string $class, string $message = '')
 * @method LazyAssertion notInstanceOf(mixed $value, string $class, string $message = '')
 * @method LazyAssertion resource(mixed $value, string $message = '')
 * @method LazyAssertion notResource(mixed $value, string $message = '')
 * @method LazyAssertion scalar(mixed $value, string $message = '')
 * @method LazyAssertion notScalar(mixed $value, string $message = '')
 * @method LazyAssertion string(mixed $value, string $message = '')
 * @method LazyAssertion notString(mixed $value, string $message = '')
 * @method LazyAssertion array(mixed $value, string $message = '')
 * @method LazyAssertion notArray(mixed $value, string $message = '')
 * @method LazyAssertion countable(mixed $value, string $message = '')
 * @method LazyAssertion notCountable(mixed $value, string $message = '')
 * @method LazyAssertion iterable(mixed $value, string $message = '')
 * @method LazyAssertion notIterable(mixed $value, string $message = '')
 */
// @phpstan-ignore missingType.iterableValue
#[DontDocument]
class LazyAssertion
{
	/**
	 * @phpstan-var array<string,array<int,InvalidArgumentException>> $exceptions
	 */
	private array $exceptions = [];
	private string $chainName = '';
	private object|string $currentChain = Assert::class;

	public function __construct() {}

	/**
	 * Proxies any Assert method call, catching InvalidArgumentException instead of
	 * re-throwing it. Failures are stored under the current chain name and reported
	 * together when validate() is called.
	 *
	 * @param array<mixed> $arguments
	 */
	public function __call(string $name, array $arguments): static
	{
		try {
			// @phpstan-ignore argument.type
			call_user_func_array([$this->currentChain, $name], $arguments);
		} catch (InvalidArgumentException $e) {
			$this->exceptions[$this->chainName][] = $e;
		}

		return $this;
	}

	/**
	 * Switches the active assertion chain to a new value and chain name.
	 * Subsequent assertion calls will operate on $value and store any failures
	 * under the $chainName key in the exception map.
	 *
	 * @return AssertionChain
	 *
	 * @phpstan-ignore return.phpDocType
	 */
	public function that(mixed $value, string $chainName): static
	{
		$this->chainName = $chainName;
		$this->currentChain = Assert::that($value);

		return $this;
	}

	/**
	 * Finalises the lazy assertion run. Throws LazyAssertionException containing a
	 * numbered list of every failure accumulated across all chains when at least one
	 * assertion failed. Does nothing when all assertions passed.
	 *
	 * @throws LazyAssertionException
	 */
	public function validate(): void
	{
		if (!empty($this->exceptions)) {
			throw LazyAssertionException::init($this->exceptions);
		}
	}
}
