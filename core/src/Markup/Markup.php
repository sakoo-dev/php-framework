<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Markup;

/**
 * Contract for Markdown (or any structured markup) document builders.
 *
 * Implementations accumulate markup fragments into an internal buffer and expose
 * them as a single string via get() / __toString(). Every method appends to that
 * buffer so calls can be made in any order and the result reflects the call sequence.
 *
 * The interface extends PHP's native Stringable so any Markup instance can be cast
 * to a string directly, enabling use in string interpolation and echo without an
 * explicit get() call.
 *
 * All heading levels (h1–h6), inline and block elements (code, ul, hr, br, link,
 * image, checklist, callout, tiny), and raw write helpers are represented as named
 * methods, keeping formatting concerns out of the callers and making it easy to swap
 * the concrete renderer (e.g. Markdown → HTML) by rebinding Markup in the container.
 */
interface Markup extends \Stringable
{
	/**
	 * Appends $value as a raw string without any trailing newline or markup.
	 */
	public function write(string $value): void;

	/**
	 * Appends $value followed by a single newline character.
	 */
	public function writeLine(string $value): void;

	/**
	 * Appends a single blank line (soft line break).
	 */
	public function br(): void;

	/**
	 * Appends a full blank line separator (forced / hard line break).
	 */
	public function fbr(): void;

	/**
	 * Appends $value as a highlighted callout block (e.g. a Markdown blockquote
	 * or a GitHub-flavoured alert).
	 */
	public function callout(string $value): void;

	/**
	 * Appends $value as a level-1 heading.
	 */
	public function h1(string $value): void;

	/**
	 * Appends $value as a level-2 heading.
	 */
	public function h2(string $value): void;

	/**
	 * Appends $value as a level-3 heading.
	 */
	public function h3(string $value): void;

	/**
	 * Appends $value as a level-4 heading.
	 */
	public function h4(string $value): void;

	/**
	 * Appends $value as a level-5 heading.
	 */
	public function h5(string $value): void;

	/**
	 * Appends $value as a level-6 heading.
	 */
	public function h6(string $value): void;

	/**
	 * Appends $value as an unordered list item.
	 */
	public function ul(string $value): void;

	/**
	 * Appends a hyperlink with the given $url and $text label.
	 */
	public function link(string $url, string $text): void;

	/**
	 * Appends an image element with the given $path source and $alt text.
	 */
	public function image(string $path, string $alt): void;

	/**
	 * Appends $value as a checklist item. The item is rendered checked when
	 * $checked is true, unchecked otherwise.
	 */
	public function checklist(string $value, bool $checked = false): void;

	/**
	 * Appends a horizontal rule divider.
	 */
	public function hr(): void;

	/**
	 * Appends $value as a fenced code block, annotated with $language for
	 * syntax highlighting.
	 */
	public function code(string $value, string $language): void;

	/**
	 * Appends $value wrapped in inline code backticks.
	 */
	public function inlineCode(string $value): void;

	/**
	 * Appends $value as small/diminished text (e.g. HTML <small> or a Markdown
	 * convention for supplementary notes).
	 */
	public function tiny(string $value): void;

	/**
	 * Returns the entire accumulated markup buffer as a plain string.
	 */
	public function get(): string;
}
