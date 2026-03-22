<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console;

use Sakoo\Framework\Core\Console\Components\RadioButton;

class Input
{
	/** @var array<string> */
	private array $arguments = [];
	/** @var array<string> */
	private array $options = [];

	/**
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

	/** @param array<string> $args */
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

	/** @return array<string> */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	public function getArgument(int $position): ?string
	{
		return $this->arguments[$position] ?? null;
	}

	/** @return array<string> */
	public function getOptions(): array
	{
		return $this->options;
	}

	public function hasOption(string $name): bool
	{
		return isset($this->options[$name]);
	}

	public function getOption(string $name): ?string
	{
		return $this->options[$name] ?? null;
	}

	public function getUserInput(): string
	{
		return readline() ?: '';
	}

	/** @param string[] $options */
	public function radio(array $options, string $title = 'Select an option:'): string
	{
		return (new RadioButton($title, $options))->show();
	}

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

	private function getShortOption(string $arg): void
	{
		$option = substr($arg, 1);
		$this->options[$option] = 'true';
	}
}
