<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Markup;

/**
 * GitHub-Flavored Markdown implementation of the Markup contract.
 *
 * Accumulates a Markdown string in a private buffer and exposes the full Markup
 * API as thin wrappers around write() and writeLine(). The rendered document
 * uses standard GFM syntax: fenced code blocks with language identifiers, >
 * blockquotes for callouts, <sub><sup> for tiny text, and <br> for forced line
 * breaks within paragraphs.
 *
 * The internal buffer is append-only; there is no mechanism to rewind or edit
 * previously written content. Instances should be constructed fresh for each
 * document generation run.
 *
 * Implements Stringable (via the Markup interface) so the finished document can
 * be retrieved by casting the instance to string without calling get() explicitly.
 */
class Markdown implements Markup
{
	private string $markdown = '';

	public function __construct() {}

	/**
	 * Appends $value to the buffer without any trailing newline.
	 */
	public function write(string $value): void
	{
		$this->markdown .= $value;
	}

	/**
	 * Appends $value to the buffer followed by a blank line (double PHP_EOL),
	 * producing a paragraph break in the rendered output.
	 */
	public function writeLine(string $value): void
	{
		$this->write($value);
		$this->br();
	}

	/**
	 * Appends a blank line (double PHP_EOL) to produce a paragraph break.
	 */
	public function br(): void
	{
		$this->markdown .= PHP_EOL . PHP_EOL;
	}

	/**
	 * Appends an HTML <br> tag for a forced inline line break that does not
	 * create a full paragraph gap.
	 */
	public function fbr(): void
	{
		$this->markdown .= '<br>';
	}

	/**
	 * Appends $value as a GFM blockquote (> prefix), used for @throws callouts
	 * in documentation output.
	 */
	public function callout(string $value): void
	{
		$this->writeLine('> ' . $value);
	}

	/** Appends $value as a level-1 heading (# prefix). */
	public function h1(string $value): void
	{
		$this->writeLine('# ' . $value);
	}

	/** Appends $value as a level-2 heading (## prefix). */
	public function h2(string $value): void
	{
		$this->writeLine('## ' . $value);
	}

	/** Appends $value as a level-3 heading (### prefix). */
	public function h3(string $value): void
	{
		$this->writeLine('### ' . $value);
	}

	/** Appends $value as a level-4 heading (#### prefix). */
	public function h4(string $value): void
	{
		$this->writeLine('#### ' . $value);
	}

	/** Appends $value as a level-5 heading (##### prefix). */
	public function h5(string $value): void
	{
		$this->writeLine('##### ' . $value);
	}

	/** Appends $value as a level-6 heading (###### prefix). */
	public function h6(string $value): void
	{
		$this->writeLine('###### ' . $value);
	}

	/**
	 * Appends $value as an unordered list item (- prefix).
	 */
	public function ul(string $value): void
	{
		$this->writeLine('- ' . $value);
	}

	/**
	 * Appends an inline hyperlink [text](url) without a trailing newline.
	 */
	public function link(string $url, string $text): void
	{
		$this->write("[$text]($url)");
	}

	/**
	 * Appends an inline image ![alt](path) by prepending '!' to a link().
	 */
	public function image(string $path, string $alt): void
	{
		$this->write('!');
		$this->link($path, $alt);
	}

	/**
	 * Appends $value as a GFM task-list item. The checkbox marker is 'X' when
	 * $checked is true, empty otherwise.
	 */
	public function checklist(string $value, bool $checked = false): void
	{
		$checked = $checked ? 'X' : '';
		$this->writeLine("[$checked] $value");
	}

	/**
	 * Appends a horizontal rule (---) as a section divider.
	 */
	public function hr(): void
	{
		$this->writeLine('---');
	}

	/**
	 * Appends $value as a fenced code block with the given $language identifier
	 * for syntax highlighting (e.g. 'php', 'bash').
	 */
	public function code(string $value, string $language): void
	{
		$this->writeLine("```$language\n$value\n```");
	}

	/**
	 * Appends $value wrapped in single backticks for inline monospace formatting.
	 */
	public function inlineCode(string $value): void
	{
		$this->write("`$value`");
	}

	/**
	 * Appends $value wrapped in <sub><sup> tags to produce small subscript text,
	 * used for PHPDoc description paragraphs in generated documentation.
	 */
	public function tiny(string $value): void
	{
		$this->writeLine("<sub><sup>$value</sup></sub>");
	}

	/**
	 * Returns the complete accumulated Markdown document.
	 */
	public function get(): string
	{
		return $this->markdown;
	}

	/**
	 * Returns the complete Markdown document, enabling (string) casting and
	 * string interpolation without an explicit get() call.
	 */
	public function __toString(): string
	{
		return $this->get();
	}
}
