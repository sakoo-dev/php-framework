<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff;

/**
 * Immutable value object representing one hunk from a unified diff.
 *
 * A hunk describes a contiguous region of change in a single file:
 *   - $startLine: 1-based line in the original file where the hunk begins
 *                 (the first line covered by the hunk, including leading context).
 *   - $originalLines: every line the hunk claims exists in the original file,
 *                     in order — context lines (' ') and removed lines ('-').
 *                     Used to validate the diff against the file before splicing.
 *   - $replacementLines: every line that should appear in the patched file
 *                        for this region — context lines (' ') and added lines ('+').
 *
 * Storing both sides lets {@see PatchApplier} verify that the diff matches the
 * file exactly before mutating it, refusing to apply when the LLM has drifted
 * line numbers or hallucinated context. This is the key safety property that
 * separates a correct unified-diff applier from one that silently corrupts files.
 *
 * Construction starts from a parsed {@see DiffHeader} via {@see self::fromHeader()}
 * and grows through {@see self::withRemovedLine()} / {@see self::withAddedLine()} /
 * {@see self::withContextLine()} as each body line is processed.
 */
final readonly class DiffHunk
{
	/**
	 * @param string[] $originalLines    context + removed, in hunk order
	 * @param string[] $replacementLines context + added, in hunk order
	 */
	public function __construct(
		public int $startLine,
		public array $originalLines,
		public array $replacementLines,
	) {}

	public static function fromHeader(DiffHeader $header): self
	{
		return new self(
			startLine: $header->startLine,
			originalLines: [],
			replacementLines: [],
		);
	}

	public function withRemovedLine(string $content): self
	{
		return new self(
			$this->startLine,
			[...$this->originalLines, $content],
			$this->replacementLines,
		);
	}

	public function withAddedLine(string $content): self
	{
		return new self(
			$this->startLine,
			$this->originalLines,
			[...$this->replacementLines, $content],
		);
	}

	public function withContextLine(string $content): self
	{
		return new self(
			$this->startLine,
			[...$this->originalLines, $content],
			[...$this->replacementLines, $content],
		);
	}

	/**
	 * Number of lines this hunk occupies in the original file.
	 */
	public function originalLength(): int
	{
		return count($this->originalLines);
	}
}
