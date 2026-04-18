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
 * Full-file semantics: reads the complete file via readLines() (no character
 * cap) so patches applied to large files cannot silently truncate the result.
 */
final class PatchApplier
{
	/**
	 * Parses the diff, splices hunks into the file in reverse order (to keep
	 * line offsets stable), and writes the result back.
	 *
	 * @throws MalformedDiffException when the diff is unparseable or contains no hunks
	 * @throws PatchWriteException    when the final write to disk fails
	 */
	public function apply(string $path, string $diff): void
	{
		$hunks = $this->parseHunks($diff);

		if ([] === $hunks) {
			throw MalformedDiffException::noHunks();
		}

		$file = File::open(Disk::Local, $path);
		$lines = array_map(
			fn (string $line): string => rtrim($line, "\n"),
			$file->readLines(),
		);

		foreach (array_reverse($hunks) as $hunk) {
			array_splice($lines, $hunk->startLine - 1, $hunk->linesToRemove, $hunk->replacementLines);
		}

		$written = $file->write(implode("\n", $lines));

		if (!$written) {
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

		foreach (explode("\n", $diff) as $line) {
			if (DiffHeader::isHeader($line)) {
				if (null !== $openHunk) {
					$hunks[] = $openHunk;
				}
				$openHunk = DiffHunk::fromHeader(DiffHeader::parse($line));

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

	private function applyBodyLine(DiffHunk $hunk, string $line): DiffHunk
	{
		$sigil = $line[0] ?? '';
		$content = substr($line, 1);

		return match ($sigil) {
			'-' => $hunk->withRemovedLine(),
			'+' => $hunk->withAddedLine($content),
			' ' => $hunk->withContextLine($content),
			default => $hunk,
		};
	}
}
