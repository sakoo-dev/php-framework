<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp\Diff;

use App\Assist\AI\Mcp\Diff\Exception\MalformedDiffException;
use App\Assist\AI\Mcp\Diff\Exception\PatchWriteException;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;

/**
 * Applies a unified diff to a file on disk.
 *
 * Lives in the AI adapter layer rather than core/FileSystem because the
 * unified-diff format is an MCP/LLM concern, not a property of local storage.
 * The underlying Storage port stays format-agnostic.
 *
 * Correctness guarantees:
 *   - Every hunk is validated against the file before any mutation: context
 *     and removed lines must match the file at the claimed offset, otherwise
 *     {@see MalformedDiffException} is thrown and the file is left untouched.
 *   - Original line endings are preserved. The detected EOL of the source file
 *     ('\r\n' or '\n') is used to join the patched lines on write, so a CRLF
 *     file stays CRLF and a final-newline file keeps its final newline.
 *   - Multi-hunk diffs are applied in reverse order so that earlier hunks'
 *     line numbers are not shifted by later splices.
 */
final class PatchApplier
{
	/**
	 * Parses the diff, validates every hunk against the file, then splices
	 * hunks into the file in reverse order and writes the result back.
	 *
	 * @throws MalformedDiffException when the diff is unparseable, contains no
	 *                                hunks, or does not match the file at the
	 *                                claimed offsets
	 * @throws PatchWriteException    when the final write to disk fails
	 */
	public function apply(string $path, string $diff): void
	{
		$hunks = $this->parseHunks($diff);

		if ([] === $hunks) {
			throw MalformedDiffException::noHunks();
		}

		$file = File::open(Disk::Local, $path);
		$rawLines = $file->readLines();

		$eol = $this->detectEol($rawLines);
		$hadTrailingNewline = $this->hadTrailingNewline($rawLines);

		$lines = array_map(
			static fn (string $line): string => preg_replace('/\r?\n$/', '', $line) ?? $line,
			$rawLines,
		);

		foreach (array_reverse($hunks) as $hunk) {
			$this->validateHunk($hunk, $lines);
			array_splice($lines, $hunk->startLine - 1, $hunk->originalLength(), $hunk->replacementLines);
		}

		$content = implode($eol, $lines);

		if ($hadTrailingNewline) {
			$content .= $eol;
		}

		if (!$file->write($content)) {
			throw PatchWriteException::forPath($path);
		}
	}

	/**
	 * @return list<DiffHunk>
	 *
	 * @throws MalformedDiffException
	 */
	private function parseHunks(string $diff): array
	{
		$hunks = [];
		$openHunk = null;
		$inBody = false;

		foreach (explode("\n", rtrim($diff, "\n")) as $line) {
			$line = rtrim($line, "\r");

			if (DiffHeader::isHeader($line)) {
				if (null !== $openHunk) {
					$hunks[] = $openHunk;
				}
				$openHunk = DiffHunk::fromHeader(DiffHeader::parse($line));
				$inBody = true;

				continue;
			}

			if (!$inBody) {
				continue;
			}

			if (str_starts_with($line, '\\')) {
				continue;
			}

			if (null === $openHunk) {
				continue;
			}

			$openHunk = $this->applyBodyLine($openHunk, $line);
		}

		if (null !== $openHunk) {
			$hunks[] = $openHunk;
		}

		return $hunks;
	}

	/**
	 * @throws MalformedDiffException
	 */
	private function applyBodyLine(DiffHunk $hunk, string $line): DiffHunk
	{
		if ('' === $line) {
			throw MalformedDiffException::emptyBodyLine();
		}

		$sigil = $line[0];
		$content = substr($line, 1);

		return match ($sigil) {
			'-' => $hunk->withRemovedLine($content),
			'+' => $hunk->withAddedLine($content),
			' ' => $hunk->withContextLine($content),
			default => throw MalformedDiffException::emptyBodyLine(),
		};
	}

	/**
	 * Verifies that the hunk's claimed original lines match the file at the
	 * claimed offset. Throws on any mismatch — the file is never partially
	 * patched.
	 *
	 * @param string[] $lines file content as 0-indexed lines without EOLs
	 *
	 * @throws MalformedDiffException
	 */
	private function validateHunk(DiffHunk $hunk, array $lines): void
	{
		$totalLines = count($lines);
		$startIndex = $hunk->startLine - 1;

		if ($startIndex < 0 || $startIndex > $totalLines) {
			throw MalformedDiffException::startLineOutOfRange($hunk->startLine, $totalLines);
		}

		foreach ($hunk->originalLines as $offset => $expected) {
			$lineIndex = $startIndex + $offset;

			if ($lineIndex >= $totalLines) {
				throw MalformedDiffException::startLineOutOfRange($lineIndex + 1, $totalLines);
			}

			if ($lines[$lineIndex] !== $expected) {
				throw MalformedDiffException::contextMismatch($lineIndex + 1, $expected, $lines[$lineIndex]);
			}
		}
	}

	/**
	 * Detects the line ending used by the source file. Defaults to "\n" when
	 * the file is empty or single-line.
	 *
	 * @param string[] $rawLines lines as returned by readLines() (with EOLs)
	 */
	private function detectEol(array $rawLines): string
	{
		foreach ($rawLines as $line) {
			if (str_ends_with($line, "\r\n")) {
				return "\r\n";
			}

			if (str_ends_with($line, "\n")) {
				return "\n";
			}
		}

		return "\n";
	}

	/**
	 * @param string[] $rawLines lines as returned by readLines() (with EOLs)
	 */
	private function hadTrailingNewline(array $rawLines): bool
	{
		if ([] === $rawLines) {
			return false;
		}

		$last = end($rawLines);

		return str_ends_with($last, "\n");
	}
}
