<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Str;

use Sakoo\Framework\Core\Regex\RegexHelper;

/**
 * Fluent, chainable multibyte string manipulation class.
 *
 * Wraps a plain PHP string and exposes a rich API for common transformations
 * (case conversion, slug/camelCase/snakeCase/kebabCase generation, trimming,
 * searching, replacing, and reversing). Every mutating method modifies the
 * internal value and returns the same instance so calls can be chained freely.
 *
 * The class also implements PHP's native Stringable contract, meaning any Str
 * instance can be cast to a plain string with (string) or embedded directly in
 * string interpolation without calling get() explicitly.
 *
 * The static factory fromType() provides a human-readable string representation
 * of any PHP value — useful for debug output and assertion messages — without
 * exposing raw var_export or print_r output.
 */
class Str implements Stringable
{
	public function __construct(private string $value = '') {}

	/**
	 * Returns the number of characters using multibyte-safe counting (mb_strlen).
	 */
	public function length(): int
	{
		return mb_strlen($this->value);
	}

	/**
	 * Capitalises the first letter of every word in the string (ucwords).
	 */
	public function uppercaseWords(): static
	{
		$this->value = ucwords($this->value);

		return $this;
	}

	/**
	 * Converts the entire string to uppercase using mb_strtoupper.
	 */
	public function uppercase(): static
	{
		$this->value = mb_strtoupper($this->value);

		return $this;
	}

	/**
	 * Converts the entire string to lowercase using mb_strtolower.
	 */
	public function lowercase(): static
	{
		$this->value = mb_strtolower($this->value);

		return $this;
	}

	/**
	 * Capitalises only the first character of the string (ucfirst).
	 */
	public function upperFirst(): static
	{
		$this->value = ucfirst($this->value);

		return $this;
	}

	/**
	 * Lowercases only the first character of the string (lcfirst).
	 */
	public function lowerFirst(): static
	{
		$this->value = lcfirst($this->value);

		return $this;
	}

	/**
	 * Reverses the byte order of the string (strrev).
	 * Note: not multibyte-safe for characters encoded in more than one byte.
	 */
	public function reverse(): static
	{
		$this->value = strrev($this->value);

		return $this;
	}

	/**
	 * Returns true when the string contains the given substring, false otherwise.
	 */
	public function contains(string $substring): bool
	{
		return str_contains($this->value, $substring);
	}

	/**
	 * Replaces every occurrence of $search with $replace (str_replace).
	 */
	public function replace(string $search, string $replace): static
	{
		$this->value = str_replace($search, $replace, $this->value);

		return $this;
	}

	/**
	 * Strips leading and trailing ASCII whitespace (trim).
	 */
	public function trim(): static
	{
		$this->value = trim($this->value);

		return $this;
	}

	/**
	 * Produces a URL-friendly slug from the current value.
	 *
	 * The algorithm splits camelCase boundaries, replaces all special characters
	 * with spaces, collapses runs of spaces into a single hyphen, trims the result,
	 * and finally lowercases everything. Equivalent to kebabCase().
	 */
	public function slug(): static
	{
		$arr = RegexHelper::findCamelCase()->split($this);

		if (is_array($arr)) {
			$this->value = implode(' ', $arr);
		}

		$str = RegexHelper::getSpecialChars()->replace($this, ' ');

		if (is_string($str)) {
			$this->value = $str;
		}

		$str = RegexHelper::getSpaceBetweenWords()->replace($this, '-');

		if (is_string($str)) {
			$this->value = $str;
		}

		$this->trim();

		return $this->lowercase();
	}

	/**
	 * Converts the string to camelCase.
	 *
	 * Splits on camelCase boundaries and special characters, lowercases all words,
	 * capitalises each word's first letter, removes all spaces, then lowercases the
	 * very first character of the resulting compound word.
	 */
	public function camelCase(): static
	{
		$arr = RegexHelper::findCamelCase()->split($this);

		if (is_array($arr)) {
			$this->value = implode(' ', $arr);
		}

		$str = RegexHelper::getSpecialChars()->replace($this, ' ');

		if (is_string($str)) {
			$this->value = $str;
		}

		$this->lowercase();
		$this->uppercaseWords();

		$str = RegexHelper::getSpaceBetweenWords()->replace($this, '');

		if (is_string($str)) {
			$this->value = $str;
		}

		$this->trim();

		return $this->lowerFirst();
	}

	/**
	 * Converts the string to snake_case.
	 *
	 * Splits on camelCase boundaries and special characters, replaces word
	 * separating spaces with underscores, trims, and lowercases the result.
	 */
	public function snakeCase(): static
	{
		$arr = RegexHelper::findCamelCase()->split($this);

		if (is_array($arr)) {
			$this->value = implode(' ', $arr);
		}

		$str = RegexHelper::getSpecialChars()->replace($this, ' ');

		if (is_string($str)) {
			$this->value = $str;
		}

		$str = RegexHelper::getSpaceBetweenWords()->replace($this, '_');

		if (is_string($str)) {
			$this->value = $str;
		}

		$this->trim();

		return $this->lowercase();
	}

	/**
	 * Converts the string to kebab-case by delegating to slug().
	 */
	public function kebabCase(): static
	{
		return $this->slug();
	}

	/**
	 * Returns the raw underlying string value.
	 */
	public function get(): string
	{
		return $this->value;
	}

	/**
	 * Casts the instance to a plain PHP string, delegating to get().
	 */
	public function __toString()
	{
		return $this->get();
	}

	/**
	 * Creates a human-readable Str representation of any PHP value.
	 *
	 * The representation is intentionally safe for logging and assertion messages:
	 * null becomes 'NULL', booleans become 'true'/'false', callables include their
	 * object hash, objects include their class hash, arrays include their count, and
	 * all scalar values are cast via strval().
	 */
	public static function fromType(mixed $value): self
	{
		if (is_null($value)) {
			return new self("'NULL'");
		}

		if (is_bool($value)) {
			return new self($value ? "'true'" : "'false'");
		}

		if (is_callable($value)) {
			return new self('Closure ' . spl_object_hash((object) $value));
		}

		if (is_object($value)) {
			return new self('Object ' . spl_object_hash($value));
		}

		if (is_array($value)) {
			return new self(sprintf('Array(%s)', count($value)));
		}

		// @phpstan-ignore argument.type
		return new self(strval($value));
	}
}
