<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder;

use Sakoo\Framework\Core\Assert\Assert;

/**
 * Parses a .gitignore file and tests whether a given filesystem path is ignored.
 *
 * Each non-comment, non-empty line in the .gitignore is converted to a PCRE
 * pattern according to simplified gitignore semantics:
 *
 * - Lines starting with '!' negate a previous match (un-ignore).
 * - Lines starting with '/' are rooted to the repository root.
 * - Trailing '/' denotes a directory pattern.
 * - Wildcards '*' and '?' are translated to their PCRE equivalents (.*  and .).
 * - All other metacharacters are quoted via preg_quote.
 *
 * Rules are evaluated in order; the last matching rule wins, mirroring real
 * git behaviour. The class is readonly because the rule set is derived entirely
 * from the file at construction time and must not change afterwards.
 */
readonly class GitIgnore
{
	/**
	 * @var array<array<string, bool|string>>
	 */
	private array $rules;
	private string $root;

	/**
	 * Constructs a GitIgnore parser from the file at $path.
	 * Defaults to '.gitignore' in the current working directory.
	 * Throws when the file does not exist.
	 */
	public function __construct(private string $path = '.gitignore')
	{
		Assert::exists($this->path, 'gitignore not found: ' . $this->path);

		// @phpstan-ignore argument.type
		$this->root = dirname(realpath($this->path));
		$this->rules = $this->parseRules();
	}

	/**
	 * Returns true when $file would be excluded by the parsed .gitignore rules.
	 *
	 * The absolute real path of $file is resolved, converted to a path relative to
	 * the repository root, and matched against all rules in order. Returns false
	 * when the path cannot be resolved or no rule matches.
	 */
	public function isIgnored(string $file): bool
	{
		$relativePath = $this->toRelativePath($file);

		$ignored = false;

		foreach ($this->rules as $rule) {
			// @phpstan-ignore argument.type
			if (preg_match($rule['regex'], $relativePath)) {
				$ignored = !$rule['negate'];
			}
		}

		return $ignored;
	}

	private function toRelativePath(string $file): string
	{
		$absolute = str_starts_with($file, $this->root) ? $file : (realpath($file) ?: $file);

		return ltrim(str_replace($this->root . '/', '', $absolute), '/');
	}

	/**
	 * Reads and parses the .gitignore file into an ordered list of rule records.
	 *
	 * Each record contains a 'regex' PCRE pattern and a 'negate' boolean flag.
	 * Comment lines (starting with '#') and blank lines are skipped. Wildcard
	 * characters are translated and rooted/directory patterns are handled before
	 * the final regex is compiled.
	 *
	 * @return array<array<string, bool|string>>
	 */
	private function parseRules(): array
	{
		$lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
		Assert::array($lines, 'Cannot read gitignore.');

		/*
		 * @var string[] $lines
		 * @phpstan-ignore varTag.nativeType
		 */
		return array_values(array_filter(array_map($this->parseLine(...), $lines)));
	}

	/**
	 * @return null|array<string, bool|string>
	 */
	private function parseLine(string $line): ?array
	{
		$line = trim($line);

		if ($this->isBlankOrComment($line)) {
			return null;
		}

		[$line, $isNegated] = $this->extractNegation($line);
		[$line, $isRooted] = $this->extractRootAnchor($line);
		$line = rtrim($line, '/');

		return [
			'regex' => $this->buildRegex($line, $isRooted),
			'negate' => $isNegated,
		];
	}

	private function isBlankOrComment(string $line): bool
	{
		return '' === $line || str_starts_with($line, '#');
	}

	/**
	 * @return array{string, bool}
	 */
	private function extractNegation(string $line): array
	{
		if (str_starts_with($line, '!')) {
			return [substr($line, 1), true];
		}

		return [$line, false];
	}

	/**
	 * @return array{string, bool}
	 */
	private function extractRootAnchor(string $line): array
	{
		if (str_starts_with($line, '/')) {
			return [substr($line, 1), true];
		}

		return [$line, false];
	}

	private function buildRegex(string $pattern, bool $isRooted): string
	{
		$escaped = $this->toRegexSegment($pattern);

		return $isRooted ? '/^' . $escaped . '(\/.*)?$/i' : '/(^|\/)' . $escaped . '(\/.*)?$/i';
	}

	private function toRegexSegment(string $pattern): string
	{
		$escaped = preg_quote($pattern, '/');
		$escaped = str_replace('\*', '.*', $escaped);

		return str_replace('\?', '.', $escaped);
	}
}
