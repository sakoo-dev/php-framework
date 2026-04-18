<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff;

/**
 * Immutable value object representing one hunk from a unified diff.
 *
 * A hunk describes a contiguous region of change in a single file:
 *   - $startLine: 1-based line in the original file where the hunk begins.
 *   - $linesToRemove: how many original lines (removed + context) to replace.
 *   - $replacementLines: the lines that replace them (added + context).
 *
 * Construction starts from a parsed {@see DiffHeader} via {@see self::fromHeader()}
 * and grows through {@see self::withRemovedLine()} / {@see self::withAddedLine()} /
 * {@see self::withContextLine()} as each body line is processed. Header parsing
 * itself lives in DiffHeader to keep this class format-agnostic.
 */
final readonly class DiffHunk
{
	/** @param string[] $replacementLines */
	public function __construct(
		public int $startLine,
		public int $linesToRemove,
		public array $replacementLines,
	) {}

	public static function fromHeader(DiffHeader $header): self
	{
		return new self(
			startLine: $header->startLine,
			linesToRemove: 0,
			replacementLines: [],
		);
	}

	public function withRemovedLine(): self
	{
		return new self($this->startLine, $this->linesToRemove + 1, $this->replacementLines);
	}

	public function withAddedLine(string $content): self
	{
		return new self($this->startLine, $this->linesToRemove, [...$this->replacementLines, $content]);
	}

	public function withContextLine(string $content): self
	{
		return new self($this->startLine, $this->linesToRemove + 1, [...$this->replacementLines, $content]);
	}
}
