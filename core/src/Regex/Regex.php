<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Regex;

use Sakoo\Framework\Core\Str\Stringable;

/**
 * Fluent regex builder and executor.
 *
 * Provides a composable, readable API for constructing PCRE regular expressions
 * programmatically, avoiding raw pattern strings scattered throughout the codebase.
 * Every builder method appends to an internal pattern string and returns the same
 * instance for chaining. The finished pattern is delimited with forward-slashes
 * when passed to PCRE functions.
 *
 * Anchors, quantifiers, character classes, lookarounds, and grouping constructs are
 * all available as named methods. Raw pattern fragments can be appended via add()
 * (unescaped) or safeAdd() (auto-escaped with preg_quote). Callable arguments passed
 * to methods that accept callable|string receive the current Regex instance so nested
 * sub-expressions can be built inline.
 *
 * Constants ALPHABET_UPPER, ALPHABET_LOWER, DIGITS, UNDERLINE, and DOT are provided
 * as pre-defined character-class ranges for use inside bracket expressions.
 */
class Regex implements \Stringable
{
	final public const string ALPHABET_UPPER = 'A-Z';
	final public const string ALPHABET_LOWER = 'a-z';
	final public const string DIGITS = '0-9';
	final public const string UNDERLINE = '_';
	final public const string DOT = '.';

	public function __construct(private string $pattern = '') {}

	/**
	 * Appends $value to the pattern after escaping all PCRE metacharacters via preg_quote.
	 */
	public function safeAdd(string $value): static
	{
		$this->add($this->escapeChars($value));

		return $this;
	}

	/**
	 * Appends a raw, unescaped fragment directly to the pattern.
	 */
	public function add(string $value): static
	{
		$this->pattern .= $value;

		return $this;
	}

	/**
	 * Appends the start-of-line anchor (^) to the pattern.
	 */
	public function startOfLine(): static
	{
		$this->add('^');

		return $this;
	}

	/**
	 * Appends the end-of-line anchor ($) to the pattern.
	 */
	public function endOfLine(): static
	{
		$this->add('$');

		return $this;
	}

	/**
	 * Appends a start-of-line anchor followed by $value, effectively asserting the
	 * pattern must match from the beginning of the subject.
	 */
	public function startsWith(callable|string $value): static
	{
		$this->startOfLine();
		$this->callOrAdd($value);

		return $this;
	}

	/**
	 * Appends $value followed by an end-of-line anchor, asserting the pattern must
	 * match at the end of the subject.
	 */
	public function endsWith(callable|string $value): static
	{
		$this->callOrAdd($value);
		$this->endOfLine();

		return $this;
	}

	/**
	 * Appends a \d digit token. When $length is greater than zero, a {n} quantifier
	 * is appended to match exactly that many digits.
	 */
	public function digit(int $length = 0): static
	{
		$this->add('\d' . ($length > 0 ? '{' . $length . '}' : ''));

		return $this;
	}

	/**
	 * Appends a non-capturing alternation group matching any one of the given strings.
	 *
	 * @param string[] $value
	 */
	public function oneOf(array $value): static
	{
		$this->wrap(fn ($exp) => $this->add(implode('|', $value)), true);

		return $this;
	}

	/**
	 * Wraps $value in a capturing group, or a non-capturing group when $nonCapturing
	 * is true. Callable $value receives the current Regex instance.
	 */
	public function wrap(callable|string $value, bool $nonCapturing = false): static
	{
		$this->add('(' . ($nonCapturing ? '?:' : ''));
		$this->callOrAdd($value);
		$this->add(')');

		return $this;
	}

	/**
	 * Wraps $value in a bracket expression [...]. Useful for building character classes.
	 */
	public function bracket(callable|string $value): static
	{
		$this->add('[');
		$this->callOrAdd($value);
		$this->add(']');

		return $this;
	}

	/**
	 * Appends $value (escaped) followed by ? making the preceding token optional.
	 */
	public function maybe(string $value): static
	{
		$this->safeAdd($value);
		$this->add('?');

		return $this;
	}

	/**
	 * Appends .* matching any character zero or more times (greedy).
	 */
	public function anything(): static
	{
		$this->add('.*');

		return $this;
	}

	/**
	 * Appends .+ matching any character one or more times (greedy).
	 */
	public function something(): static
	{
		$this->add('.+');

		return $this;
	}

	/**
	 * Appends \n matching a Unix line break.
	 */
	public function unixLineBreak(): static
	{
		$this->add('\n');

		return $this;
	}

	/**
	 * Appends \r\n matching a Windows line break.
	 */
	public function windowsLineBreak(): static
	{
		$this->add('\r\n');

		return $this;
	}

	/**
	 * Appends \t matching a horizontal tab character.
	 */
	public function tab(): static
	{
		$this->add('\t');

		return $this;
	}

	/**
	 * Appends \s matching any whitespace character.
	 */
	public function space(): static
	{
		$this->add('\s');

		return $this;
	}

	/**
	 * Appends \w matching any word character (letter, digit, or underscore).
	 */
	public function word(): static
	{
		$this->add('\w');

		return $this;
	}

	/**
	 * Appends each of the given raw character fragments to the pattern in order.
	 */
	public function chars(string ...$values): static
	{
		$this->add(implode('', $values));

		return $this;
	}

	/**
	 * Appends a negated character class [^...]*  matching any character NOT in the
	 * set defined by $value, zero or more times.
	 */
	public function anythingWithout(callable|string $value): static
	{
		$this->add('[^');
		$this->callOrAdd($value);
		$this->add(']*');

		return $this;
	}

	/**
	 * Appends a negated character class [^...]+ matching any character NOT in the
	 * set defined by $value, one or more times.
	 */
	public function somethingWithout(callable|string $value): static
	{
		$this->add('[^');
		$this->callOrAdd($value);
		$this->add(']+');

		return $this;
	}

	/**
	 * Appends a character class [...]* matching any character IN the set defined
	 * by $value, zero or more times.
	 */
	public function anythingWith(callable|string $value): static
	{
		$this->add('[');
		$this->callOrAdd($value);
		$this->add(']*');

		return $this;
	}

	/**
	 * Appends a character class [...]+ matching any character IN the set defined
	 * by $value, one or more times.
	 */
	public function somethingWith(callable|string $value): static
	{
		$this->add('[');
		$this->callOrAdd($value);
		$this->add(']+');

		return $this;
	}

	/**
	 * Escapes all PCRE metacharacters in $value using preg_quote with a / delimiter.
	 * Returns an empty string when $value is empty.
	 */
	public function escapeChars(string $value): string
	{
		if (empty($value)) {
			return '';
		}

		return preg_quote($value, '/');
	}

	/**
	 * Appends a positive lookahead (?=...) asserting that $value matches immediately
	 * ahead of the current position without consuming characters.
	 */
	public function lookahead(callable|string $value): static
	{
		$this->wrap(function (Regex $exp) use ($value): void {
			$exp->add('?=');
			$this->callOrAdd($value);
		});

		return $this;
	}

	/**
	 * Appends a positive lookbehind (?<=...) asserting that $value matches immediately
	 * behind the current position without consuming characters.
	 */
	public function lookbehind(callable|string $value): static
	{
		$this->wrap(function (Regex $exp) use ($value): void {
			$exp->add('?<=');
			$this->callOrAdd($value);
		});

		return $this;
	}

	/**
	 * Appends a negative lookahead (?!...) asserting that $value does NOT match
	 * immediately ahead of the current position.
	 */
	public function negativeLookahead(callable|string $value): static
	{
		$this->wrap(function (Regex $exp) use ($value): void {
			$exp->add('?!');
			$this->callOrAdd($value);
		});

		return $this;
	}

	/**
	 * Appends a negative lookbehind (?<!...) asserting that $value does NOT match
	 * immediately behind the current position.
	 */
	public function negativeLookbehind(callable|string $value): static
	{
		$this->wrap(function (Regex $exp) use ($value): void {
			$exp->add('?<!');
			$this->callOrAdd($value);
		});

		return $this;
	}

	/**
	 * Executes preg_match against $value and returns the full matches array.
	 * Returns an empty array when there is no match.
	 *
	 * @return string[]
	 */
	public function match(string $value): array
	{
		$matches = null;
		preg_match("/$this->pattern/", $value, $matches);

		return $matches;
	}

	/**
	 * Executes preg_match_all against $value and returns all match groups.
	 *
	 * @return string[][]
	 */
	public function matchAll(string $value): array
	{
		$matches = null;
		preg_match_all("/$this->pattern/", $value, $matches);

		return $matches;
	}

	/**
	 * Returns true when the pattern matches anywhere in $value, false otherwise.
	 */
	public function test(string $value): bool
	{
		return !empty($this->match($value));
	}

	/**
	 * Performs a preg_replace on $string, substituting every match with $replace.
	 * Accepts both plain strings and Stringable objects as the subject.
	 *
	 * @return null|string|string[]
	 */
	public function replace(string|Stringable $string, string $replace): array|string|null
	{
		return preg_replace("/$this->pattern/", $replace, "$string");
	}

	/**
	 * Splits $subject on every occurrence of the pattern using preg_split.
	 * Returns false on failure.
	 *
	 * @return false|string[]
	 */
	public function split(string|Stringable $subject): array|false
	{
		return preg_split("/$this->pattern/", "$subject");
	}

	/**
	 * Returns the raw pattern string accumulated so far.
	 */
	public function get(): string
	{
		return $this->pattern;
	}

	/**
	 * Casts the instance to its raw pattern string, delegating to get().
	 */
	public function __toString(): string
	{
		return $this->get();
	}

	/**
	 * Invokes a callable with the current Regex instance, or appends a string value
	 * via safeAdd(). This is the core dispatch used by all builder methods that accept
	 * callable|string to allow inline sub-expression construction.
	 */
	private function callOrAdd(callable|string $value): void
	{
		if (is_callable($value)) {
			$value($this);
		}

		if (is_string($value)) {
			$this->safeAdd($value);
		}
	}
}
