<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder\DTO;

/**
 * Result of a content search operation.
 *
 * Encapsulates matching lines, their file locations, and metadata about
 * result truncation. Immutable value object returned by FileSearch::grep().
 */
final readonly class GrepResult
{
	/**
	 * @param string $pattern the search pattern that was used
	 * @param GrepMatch[] $matches all lines that matched the pattern
	 * @param int $total count of matches returned (may be capped by limit)
	 * @param bool $truncated whether the result set was cut short by a limit
	 */
	public function __construct(
		public string $pattern,
		public array $matches,
		public int $total,
		public bool $truncated,
	) {}

	/**
	 * Converts to array structure for MCP structured content.
	 *
	 * @return array{pattern: string, matches: list<array{file: string, line: int, text: string}>, total: int, truncated: bool}
	 */
	public function toArray(): array
	{
		return [
			'pattern' => $this->pattern,
			'matches' => array_values(array_map(static fn (GrepMatch $m): array => $m->toArray(), $this->matches)),
			'total' => $this->total,
			'truncated' => $this->truncated,
		];
	}
}
