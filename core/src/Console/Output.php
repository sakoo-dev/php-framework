<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console;

/**
 * ANSI-aware console output writer.
 *
 * Wraps PHP's echo with optional ANSI escape-code formatting for foreground
 * colour, background colour, and text style. The Output auto-detects whether
 * the terminal supports ANSI colours at construction time; when colour support
 * is absent (e.g. Windows without ANSICON, non-TTY pipes), formatText() returns
 * plain strings with no escape codes so output remains readable.
 *
 * Every write goes through the internal $buffer array so tests can retrieve all
 * output via getBuffer() / getDisplay() without capturing stdout. Silent mode
 * suppresses all echo calls while still populating the buffer, enabling assertions
 * in unit tests without console noise.
 *
 * Constants are grouped into three namespaces:
 * - SUCCESS / ERROR          — standard process exit codes.
 * - STYLE_*                  — ANSI SGR style codes (bold, underline, blink, reverse).
 * - COLOR_* / BG_*           — ANSI foreground and background colour codes.
 */
class Output
{
	/** Standard process exit code for a successful command. */
	final public const int SUCCESS = 0;

	/** Standard process exit code for a failed command. */
	final public const int ERROR = 1;

	final public const int STYLE_NORMAL = 0;
	final public const int STYLE_BOLD = 1;
	final public const int STYLE_UNDERLINE = 4;
	final public const int STYLE_BLINK = 5;
	final public const int STYLE_REVERSE = 7;

	final public const int COLOR_BLACK = 30;
	final public const int COLOR_RED = 31;
	final public const int COLOR_GREEN = 32;
	final public const int COLOR_YELLOW = 33;
	final public const int COLOR_BLUE = 34;
	final public const int COLOR_MAGENTA = 35;
	final public const int COLOR_CYAN = 36;
	final public const int COLOR_WHITE = 37;

	final public const int BG_BLACK = 40;
	final public const int BG_RED = 41;
	final public const int BG_GREEN = 42;
	final public const int BG_YELLOW = 43;
	final public const int BG_BLUE = 44;
	final public const int BG_MAGENTA = 45;
	final public const int BG_CYAN = 46;
	final public const int BG_WHITE = 47;

	private bool $supportsColors = false;
	/** @var list<string> */
	private array $buffer = [];
	private bool $isSilentMode = false;

	/**
	 * Detects ANSI colour support automatically, or forces it on when $forceColors
	 * is true (useful in tests that assert on formatted output).
	 */
	public function __construct(bool $forceColors = false)
	{
		$this->supportsColors = $forceColors || $this->detectColorSupport();
	}

	/**
	 * Detects whether the current terminal environment supports ANSI escape codes.
	 *
	 * On Windows, checks ANSICON, ConEmuANSI, TERM, and TERM_PROGRAM environment
	 * variables. On POSIX systems, uses posix_isatty() when available, then falls
	 * back to common CI environment variables (GITHUB_ACTIONS, TRAVIS, etc.).
	 */
	private function detectColorSupport(): bool
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI') || 'xterm' === getenv('TERM') || 'Hyper' === getenv('TERM_PROGRAM');
		}

		if (function_exists('posix_isatty')) {
			return posix_isatty(STDOUT);
		}

		return getenv('GITHUB_ACTIONS') || getenv('GITLAB_CI') || getenv('TRAVIS') || getenv('CIRCLECI') || getenv('JENKINS_URL') || getenv('CI');
	}

	/**
	 * Writes two consecutive newlines to produce a blank line in the output.
	 */
	public function newLine(): void
	{
		$this->text(PHP_EOL . PHP_EOL);
	}

	/**
	 * Writes $message to stdout (unless silent mode is active) and appends it to
	 * the internal buffer.
	 */
	public function write(string $message): void
	{
		$this->buffer[] = $message;
		echo !$this->isSilentMode ? $message : '';
	}

	/**
	 * Formats $message with optional ANSI codes and writes it without a trailing
	 * newline. Useful for inline prompts or progress indicators.
	 *
	 * @param list<string>|string $message
	 */
	public function text(array|string $message, ?int $foreground = null, ?int $background = null, ?int $style = null): void
	{
		$text = $this->formatText($message, $foreground, $background, $style);
		$this->write($text);
	}

	/**
	 * Formats $message with optional ANSI codes and writes it followed by a trailing
	 * newline. This is the standard method for printing a complete line of output.
	 *
	 * @param list<string>|string $message
	 */
	public function block(array|string $message, ?int $foreground = null, ?int $background = null, ?int $style = null): void
	{
		$text = $this->formatText($message, $foreground, $background, $style);
		$this->write($text . PHP_EOL);
	}

	/**
	 * Writes $message in bold green, indicating a successful outcome.
	 *
	 * @param list<string>|string $message
	 */
	public function success(array|string $message): void
	{
		$this->block($message, self::COLOR_GREEN, null, self::STYLE_BOLD);
	}

	/**
	 * Writes $message in bold blue, indicating an informational notice.
	 *
	 * @param list<string>|string $message
	 */
	public function info(array|string $message): void
	{
		$this->block($message, self::COLOR_BLUE, null, self::STYLE_BOLD);
	}

	/**
	 * Writes $message in bold yellow, indicating a non-fatal warning.
	 *
	 * @param list<string>|string $message
	 */
	public function warning(array|string $message): void
	{
		$this->block($message, self::COLOR_YELLOW, null, self::STYLE_BOLD);
	}

	/**
	 * Writes $message in bold red, indicating a fatal error or failure.
	 *
	 * @param list<string>|string $message
	 */
	public function error(array|string $message): void
	{
		$this->block($message, self::COLOR_RED, null, self::STYLE_BOLD);
	}

	/**
	 * Enables or disables silent mode. When active, write() populates the buffer
	 * but suppresses all echo output. Useful for capturing output in tests.
	 */
	public function setSilentMode(bool $isSilentMode = true): void
	{
		$this->isSilentMode = $isSilentMode;
	}

	/**
	 * Returns true when the current environment supports ANSI colour codes.
	 */
	public function supportsColors(): bool
	{
		return $this->supportsColors;
	}

	/**
	 * Returns all strings written since the Output was constructed.
	 *
	 * @return list<string>
	 */
	public function getBuffer(): array
	{
		return $this->buffer;
	}

	/**
	 * Returns the entire buffered output as a single concatenated string,
	 * equivalent to imploding getBuffer() with an empty separator.
	 */
	public function getDisplay(): string
	{
		return implode('', $this->getBuffer());
	}

	/**
	 * Wraps $message in ANSI SGR escape codes for the given foreground colour,
	 * background colour, and text style. Returns the plain message when the
	 * terminal does not support colours or no formatting parameters are given.
	 * Arrays are joined with PHP_EOL before formatting.
	 *
	 * @param list<string>|string $message
	 */
	public function formatText(array|string $message, ?int $foreground = null, ?int $background = null, ?int $style = null): string
	{
		if (is_array($message)) {
			$message = implode(PHP_EOL, $message);
		}

		if (!$this->supportsColors()) {
			return $message;
		}

		$format = [];

		if (!empty($style)) {
			$format[] = $style;
		}

		if (!empty($foreground)) {
			$format[] = $foreground;
		}

		if (!empty($background)) {
			$format[] = $background;
		}

		if (empty($format)) {
			return $message;
		}

		return sprintf("\033[%sm%s\033[0m", implode(';', $format), $message);
	}
}
