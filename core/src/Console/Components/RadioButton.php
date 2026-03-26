<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console\Components;

/**
 * Interactive terminal radio-button selection component.
 *
 * Presents a list of options to the user and returns the one they select.
 * Two rendering modes are supported:
 *
 * - Interactive mode — used when running in a real TTY with stty available.
 *   Renders a live-updating list using ANSI cursor control; the user navigates
 *   with ↑/↓ arrow keys or j/k, selects with Enter, and cancels with Esc or Ctrl-C.
 *   Numeric keys 1–N jump directly to that option.
 *
 * - Fallback mode — used in non-interactive environments (pipes, CI, Windows).
 *   Prints a numbered list and prompts for a numeric input, re-prompting on
 *   invalid input until a valid selection is made.
 *
 * Terminal raw mode is managed via stty: the original settings are captured before
 * entering interactive mode and always restored in a finally block, even if an
 * exception is thrown, to prevent leaving the terminal in a broken state.
 */
class RadioButton
{
	/** @var string[] */
	private array $options;
	private int $selected = 0;
	private string $prompt;

	/**
	 * @param string[] $options The list of selectable option strings. Must not be empty.
	 *
	 * @throws \InvalidArgumentException when $options is empty
	 */
	public function __construct(string $prompt, array $options)
	{
		$this->prompt = $prompt;
		// @phpstan-ignore assign.propertyType
		$this->options = array_values($options);

		if (empty($this->options)) {
			throw new \InvalidArgumentException('Options cannot be empty');
		}
	}

	/**
	 * Displays the radio-button prompt and blocks until the user makes a selection.
	 * Uses interactive mode when a real TTY with stty is available, otherwise falls
	 * back to a numbered list prompt. Returns the selected option string.
	 */
	public function show(): string
	{
		if ($this->isInteractive() && $this->hasStty()) {
			return $this->interactiveMode();
		}

		return $this->fallbackMode();
	}

	/**
	 * Returns true when the process is running in a CLI SAPI with an interactive
	 * STDIN TTY, indicating that raw terminal input and cursor control are available.
	 */
	private function isInteractive(): bool
	{
		return 'cli' === php_sapi_name()
			&& (function_exists('posix_isatty') ? @posix_isatty(STDIN) : true);
	}

	/**
	 * Returns true when running on a non-Windows OS with stty available in PATH,
	 * which is required for entering raw terminal mode.
	 */
	private function hasStty(): bool
	{
		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		return false === stripos(PHP_OS, 'WIN') && null !== @shell_exec('which stty 2>/dev/null');
	}

	/**
	 * Runs the interactive arrow-key selection loop.
	 *
	 * Saves the current stty settings, switches to raw (-icanon -echo) mode, renders
	 * the option list, then reads single keystrokes until Enter is pressed. The stty
	 * settings are always restored in a finally block. Returns the selected option.
	 */
	private function interactiveMode(): string
	{
		/** @phpstan-ignore sakoo.vulnerability.dangerousFunctions */
		$sttyMode = shell_exec('stty -g');
		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		shell_exec('stty -icanon -echo');

		try {
			$this->render();

			while (true) {
				$char = fread(STDIN, 3);

				if ("\n" === $char || "\r" === $char) {
					break;
				}

				if ("\033[A" === $char || 'k' === $char) {
					$this->selected = ($this->selected - 1 + count($this->options)) % count($this->options);
					$this->render();
				} elseif ("\033[B" === $char || 'j' === $char) {
					$this->selected = ($this->selected + 1) % count($this->options);
					$this->render();
				} elseif (is_numeric($char) && $char >= 1 && $char <= count($this->options)) {
					$this->selected = (int) $char - 1;
					$this->render();
				} elseif ("\033" === $char || "\003" === $char) {
					// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
					shell_exec("stty $sttyMode");
					echo "\n\nCancelled.\n";

					exit(1);
				}
			}

			$result = $this->options[$this->selected];

			$this->clearLines(count($this->options) + 2);
			echo $this->prompt . "\n";
			echo '✓ Selected: ' . $result . "\n\n";

			return $result;
		} finally {
			// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
			shell_exec("stty $sttyMode");
		}
	}

	/**
	 * Runs the non-interactive numbered-list selection loop.
	 *
	 * Prints a numbered list of options and prompts for a numeric choice,
	 * repeating until a valid integer in range is entered. Returns the selected option.
	 */
	private function fallbackMode(): string
	{
		echo $this->prompt . "\n";

		foreach ($this->options as $index => $option) {
			echo '  ' . ($index + 1) . ') ' . $option . "\n";
		}

		while (true) {
			echo "\nEnter your choice (1-" . count($this->options) . '): ';
			$input = trim(fgets(STDIN) ?: '');

			if (is_numeric($input) && $input >= 1 && $input <= count($this->options)) {
				$this->selected = (int) $input - 1;
				echo '✓ Selected: ' . $this->options[$this->selected] . "\n\n";

				return $this->options[$this->selected];
			}

			echo "Invalid choice. Please try again.\n";
		}
	}

	/**
	 * Renders the current state of the option list to the terminal, first erasing
	 * the previous render via clearLines(). The currently selected option is marked
	 * with ● and all others with ○.
	 */
	private function render(): void
	{
		$this->clearLines(count($this->options) + 2);

		echo $this->prompt . "\n";
		echo "(Use ↑/↓ or j/k to navigate, Enter to select)\n";

		foreach ($this->options as $index => $option) {
			if ($index === $this->selected) {
				echo '● ' . $option . "\n";
			} else {
				echo '○ ' . $option . "\n";
			}
		}
	}

	/**
	 * Erases $count lines above the current cursor position using ANSI escape codes
	 * (\033[1A moves up one line, \033[2K erases the line). Used to redraw the
	 * option list in place on each navigation keystroke.
	 */
	private function clearLines(int $count): void
	{
		for ($i = 0; $i < $count; ++$i) {
			echo "\033[1A\033[2K";
		}
	}
}
