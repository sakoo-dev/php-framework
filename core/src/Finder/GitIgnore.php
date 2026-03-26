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
		$this->rules = $this->loadGitignore();
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
		$abs = realpath($file);

		if (false === $abs) {
			return false;
		}

		$rel = str_replace($this->root . '/', '', $abs);
		$rel = ltrim($rel, '/');

		$ignored = false;

		foreach ($this->rules as $rule) {
			// @phpstan-ignore argument.type
			if (preg_match($rule['regex'], $rel)) {
				$ignored = !$rule['negate'];
			}
		}

		return $ignored;
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
	private function loadGitignore(): array
	{
		$lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		Assert::array($lines, 'Cannot read gitignore.');

		$rules = [];

		/**
		 * @var string[] $lines
		 *
		 * @phpstan-ignore varTag.nativeType
		 */
		foreach ($lines as $line) {
			$line = trim($line);

			if ('' === $line || '#' === $line[0]) {
				continue;
			}

			$isNegation = false;

			if ('!' === $line[0]) {
				$isNegation = true;
				$line = substr($line, 1);
			}

			$isRooted = str_starts_with($line, '/');

			if ($isRooted) {
				$line = substr($line, 1);
			}

			$regex = preg_quote($line, '/');
			$regex = str_replace('\*', '.*', $regex);
			$regex = str_replace('\?', '.', $regex);

			$isDir = str_ends_with($line, '/');

			if ($isDir) {
				$regex = rtrim($regex, '\/') . '(\/.*)?';
			}

			$regex = $isRooted
				? '/^' . $regex . '$/i'
				: '/(^|\/)' . $regex . '$/i';

			$rules[] = [
				'regex' => $regex,
				'negate' => $isNegation,
			];
		}

		return $rules;
	}
}
