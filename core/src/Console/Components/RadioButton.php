<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console\Components;

class RadioButton
{
	/** @var string[] */
	private array $options;
	private int $selected = 0;
	private string $prompt;

	/** @param string[] $options */
	public function __construct(string $prompt, array $options)
	{
		$this->prompt = $prompt;
		// @phpstan-ignore assign.propertyType
		$this->options = array_values($options);

		if (empty($this->options)) {
			throw new \InvalidArgumentException('Options cannot be empty');
		}
	}

	public function show(): string
	{
		if ($this->isInteractive() && $this->hasStty()) {
			return $this->interactiveMode();
		}

		return $this->fallbackMode();
	}

	private function isInteractive(): bool
	{
		return 'cli' === php_sapi_name()
			&& (function_exists('posix_isatty') ? @posix_isatty(STDIN) : true);
	}

	private function hasStty(): bool
	{
		// @phpstan-ignore sakoo.vulnerability.dangerousFunctions
		return false === stripos(PHP_OS, 'WIN') && null !== @shell_exec('which stty 2>/dev/null');
	}

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

	private function clearLines(int $count): void
	{
		for ($i = 0; $i < $count; ++$i) {
			echo "\033[1A\033[2K";
		}
	}
}
