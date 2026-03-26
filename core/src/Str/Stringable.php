<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Str;

/**
 * Contract for fluent, chainable string manipulation objects.
 *
 * Extends PHP's native Stringable so any implementation can be cast to a plain
 * string via (string) or string interpolation. All mutating methods return the
 * same instance (static) to enable method chaining, while get() provides
 * explicit access to the underlying string value.
 */
interface Stringable extends \Stringable
{
	/**
	 * Returns the number of characters in the string using multibyte-safe counting.
	 */
	public function length(): int;

	/**
	 * Transforms every character to uppercase using multibyte-safe conversion.
	 */
	public function uppercase(): static;

	/**
	 * Transforms every character to lowercase using multibyte-safe conversion.
	 */
	public function lowercase(): static;

	/**
	 * Capitalises the first letter of each word in the string.
	 */
	public function uppercaseWords(): static;

	/**
	 * Capitalises only the very first character of the string.
	 */
	public function upperFirst(): static;

	/**
	 * Lowercases only the very first character of the string.
	 */
	public function lowerFirst(): static;

	/**
	 * Reverses the character order of the string.
	 */
	public function reverse(): static;

	/**
	 * Returns true when the string contains the given substring, false otherwise.
	 */
	public function contains(string $substring): bool;

	/**
	 * Replaces every occurrence of $search with $replace in the string.
	 */
	public function replace(string $search, string $replace): static;

	/**
	 * Removes leading and trailing whitespace from the string.
	 */
	public function trim(): static;

	/**
	 * Converts the string to a URL-friendly slug (kebab-case, lowercase, special
	 * characters replaced by hyphens, camelCase boundaries split).
	 */
	public function slug(): static;

	/**
	 * Converts the string to camelCase, splitting on camelCase boundaries, special
	 * characters, and spaces, then lowercasing the first character of the result.
	 */
	public function camelCase(): static;

	/**
	 * Returns the current underlying string value.
	 */
	public function get(): string;
}
