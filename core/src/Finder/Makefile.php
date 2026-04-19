<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Assert\Assert;

/**
 * Parses a Makefile and exposes its targets with their recipe lines.
 *
 * Reads the file at construction time and builds a map of target name → recipe
 * lines. Each line is a tab-indented shell command; leading tabs and @ prefixes
 * are stripped so the strings contain the bare command text.
 */
readonly class Makefile
{
	/** @var string[] */
	private array $lines;

	/**
	 * Constructs a Makefile parser from the file at $path. Defaults to 'Makefile'
	 * in the current working directory. Throws when the file does not exist.
	 */
	public function __construct(private string $path = 'Makefile')
	{
		Assert::exists($this->path, 'Makefile not found: ' . $this->path);

		$this->lines = file($this->path, FILE_IGNORE_NEW_LINES) ?: [];
	}

	/**
	 * Returns all Makefile targets as a map of target name → recipe lines.
	 *
	 * Targets are discovered by scanning for lines matching `name:` at the start.
	 * Recipe lines (tab-indented) are collected until the next target definition.
	 *
	 * @return array<string, string[]>
	 */
	public function getTargets(): array
	{
		$targets = [];
		$currentTarget = null;

		foreach ($this->lines as $line) {
			if ($this->isTargetDefinition($line)) {
				$currentTarget = $this->extractTargetName($line);
				$targets[$currentTarget] = [];
			} elseif (null !== $currentTarget && $this->isRecipeLine($line)) {
				$targets[$currentTarget][] = $this->cleanRecipeLine($line);
			}
		}

		return $targets;
	}

	/**
	 * Returns true when $line defines a Makefile target (starts with `name:`).
	 */
	private function isTargetDefinition(string $line): bool
	{
		return 1 === preg_match('/^[a-zA-Z_-]+:/', $line);
	}

	/**
	 * Extracts the target name from a target definition line.
	 */
	private function extractTargetName(string $line): string
	{
		preg_match('/^([a-zA-Z_-]+):/', $line, $matches);

		return $matches[1];
	}

	/**
	 * Returns true when $line is a recipe line (starts with a tab character).
	 */
	private function isRecipeLine(string $line): bool
	{
		return str_starts_with($line, "\t");
	}

	/**
	 * Strips the leading tab and optional @ prefix from a recipe line.
	 */
	private function cleanRecipeLine(string $line): string
	{
		return trim($line, "\t@");
	}
}
