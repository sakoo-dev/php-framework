<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder\DTO;

/**
 * Result of a file name search operation.
 *
 * Encapsulates matched file paths and metadata about result truncation.
 * Immutable value object returned by FileSearch::find().
 */
final readonly class FindResult
{
	/**
	 * @param string $pattern the glob pattern that was used
	 * @param string[] $files relative paths of files matching the pattern
	 * @param int $total count of files returned (may be capped by limit)
	 * @param bool $truncated whether the result set was cut short by a limit
	 */
	public function __construct(
		public string $pattern,
		public array $files,
		public int $total,
		public bool $truncated,
	) {}

	/**
	 * @return array{pattern: string, files: list<string>, total: int, truncated: bool}
	 */
	public function toArray(): array
	{
		return [
			'pattern' => $this->pattern,
			'files' => array_values($this->files),
			'total' => $this->total,
			'truncated' => $this->truncated,
		];
	}
}
