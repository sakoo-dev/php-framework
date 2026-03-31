<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Assert\Assert;

readonly class Makefile
{
	/** @var string[] */
	private array $lines;

	public function __construct(private string $path = 'Makefile')
	{
		Assert::exists($this->path, 'Makefile not found: ' . $this->path);

		$this->lines = file($this->path, FILE_IGNORE_NEW_LINES) ?: [];
	}

	/** @return array<string, string[]> */
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

	private function isTargetDefinition(string $line): bool
	{
		return 1 === preg_match('/^[a-zA-Z_-]+:/', $line);
	}

	private function extractTargetName(string $line): string
	{
		preg_match('/^([a-zA-Z_-]+):/', $line, $matches);

		return $matches[1];
	}

	private function isRecipeLine(string $line): bool
	{
		return str_starts_with($line, "\t");
	}

	private function cleanRecipeLine(string $line): string
	{
		return trim($line, "\t@");
	}
}
