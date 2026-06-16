<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder\DTO;

/**
 * Single line match from a grep operation.
 *
 * Immutable value object representing one line of text that matched
 * a search pattern, including its location (file and line number).
 */
final readonly class GrepMatch
{
	/**
	 * @param string $file relative path to the file containing the match
	 * @param int $line 1-based line number where the match was found
	 * @param string $text the matched line content (trimmed)
	 */
	public function __construct(
		public string $file,
		public int $line,
		public string $text,
	) {}

	/**
	 * @return array{file: string, line: int, text: string}
	 */
	public function toArray(): array
	{
		return [
			'file' => $this->file,
			'line' => $this->line,
			'text' => $this->text,
		];
	}
}
