<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console;

use Sakoo\Framework\Core\Console\Components\RadioButton;

/**
 * Parses and exposes CLI arguments and options for a console command.
 *
 * On construction the raw $argv array is parsed into two separate collections:
 *
 * - Arguments — positional values with no leading dashes, stored by zero-based
 *   integer index in the order they appear on the command line.
 * - Options   — named flags prefixed with '--' (long) or '-' (short):
 *     - Long options:  '--name=value' stores key 'name' with 'value';
 *                      '--flag' stores key 'flag' with 'true'.
 *     - Short options: '-x' stores key 'x' with 'true'. Values are not
 *                      supported for short options.
 *
 * When no $args array is passed to the constructor, $_SERVER['argv'] is used
 * automatically and the script name (argv[0]) is stripped, matching standard
 * PHP CLI behaviour.
 */
class Input
{
	/** @var array<string> */
	private array $arguments = [];
	/** @var array<string> */
	private array $options = [];

	/**
	 * Parses $args into positional arguments and named options. When $args is null,
	 * the global $_SERVER['argv'] array is used with the script name removed.
	 *
	 * @param null|array<string> $args
	 */
	public function __construct(?array $args = null)
	{
		if (null === $args) {
			/** @var array<string> $args */
			$args = $_SERVER['argv'] ?? [];
			array_shift($args);
		}

		$this->parseArgs($args);
	}

	/**
	 * Iterates $args and classifies each token as a long option (--), short option
	 * (-), or positional argument, populating the respective internal collections.
	 *
	 * @param array<string> $args
	 */
	private function parseArgs(array $args): void
	{
		$currentIndex = 0;

		foreach ($args as $arg) {
			if (str_starts_with($arg, '--')) {
				$this->getLongOption($arg);

				continue;
			}

			if (str_starts_with($arg, '-')) {
				$this->getShortOption($arg);

				continue;
			}

			$this->arguments[$currentIndex] = $arg;
			++$currentIndex;
		}
	}

	/**
	 * Returns all positional arguments indexed by their zero-based position.
	 *
	 * @return array<string>
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	/**
	 * Returns the positional argument at $position, or null when the position
	 * does not exist.
	 */
	public function getArgument(int $position): ?string
	{
		return $this->arguments[$position] ?? null;
	}

	/**
	 * Returns all parsed options as an associative array of name → value strings.
	 *
	 * @return array<string>
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * Returns true when an option with the given $name was present on the command
	 * line, false otherwise.
	 */
	public function hasOption(string $name): bool
	{
		return isset($this->options[$name]);
	}

	/**
	 * Returns the value of the named option, or null when the option was not
	 * provided. Boolean flags store the string 'true' as their value.
	 */
	public function getOption(string $name): ?string
	{
		return $this->options[$name] ?? null;
	}

	/**
	 * Reads a line of text from the terminal (via readline) and returns it.
	 * Returns an empty string when readline produces no input.
	 */
	public function getUserInput(): string
	{
		return readline() ?: '';
	}

	/**
	 * Displays an interactive radio-button selection prompt with the given $options
	 * and $title, and returns the option chosen by the user.
	 *
	 * @param string[] $options
	 */
	public function radio(array $options, string $title = 'Select an option:'): string
	{
		return (new RadioButton($title, $options))->show();
	}

	/**
	 * Parses a long option token (--name or --name=value) into the options map.
	 * Tokens without '=' are stored with the value 'true'.
	 */
	private function getLongOption(string $arg): void
	{
		$option = substr($arg, 2);

		if (str_contains($option, '=')) {
			[$name, $value] = explode('=', $option, 2);
			$this->options[$name] = $value;
		} else {
			$this->options[$option] = 'true';
		}
	}

	/**
	 * Parses a short option token (-x) into the options map with the value 'true'.
	 */
	private function getShortOption(string $arg): void
	{
		$option = substr($arg, 1);
		$this->options[$option] = 'true';
	}
}
