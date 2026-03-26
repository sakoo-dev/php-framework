<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Regex;

/**
 * Factory for the pre-built Regex instances used internally by the Str class.
 *
 * Centralises the recurring regular expression patterns required for case
 * conversion and string normalisation so they are defined once and reused
 * consistently across the codebase.
 */
class RegexHelper
{
	/**
	 * Returns a Regex that matches the zero-width boundary between a lowercase letter
	 * and an uppercase letter — the split point used to separate camelCase words.
	 */
	public static function findCamelCase(): Regex
	{
		return (new Regex())
			->lookbehind(fn (Regex $exp) => $exp->add('[' . Regex::ALPHABET_LOWER . ']'))
			->lookahead(fn (Regex $exp) => $exp->add('[' . Regex::ALPHABET_UPPER . ']'));
	}

	/**
	 * Returns a Regex that matches a single whitespace character preceded and followed
	 * by a word character — i.e. the space between two words rather than leading or
	 * trailing whitespace.
	 */
	public static function getSpaceBetweenWords(): Regex
	{
		return (new Regex())
			->lookbehind(fn (Regex $exp) => $exp->word())
			->space()
			->lookahead(fn (Regex $exp) => $exp->word());
	}

	/**
	 * Returns a Regex that matches one or more characters that are NOT letters,
	 * digits, or underscores — effectively any "special" or punctuation character.
	 */
	public static function getSpecialChars(): Regex
	{
		return (new Regex())
			->somethingWithout(
				fn (Regex $exp) => $exp->add(Regex::ALPHABET_LOWER . Regex::ALPHABET_UPPER . Regex::DIGITS)
			);
	}
}
