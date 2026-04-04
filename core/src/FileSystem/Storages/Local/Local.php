<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\FileSystem\Storages\Local;

use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\FileSystem\Storage;
use Sakoo\Framework\Core\Finder\FileFinder;

/**
 * Local-disk implementation of the Storage interface.
 *
 * Fulfils the full Storage contract using standard PHP filesystem functions
 * (file_exists, mkdir, touch, rename, chmod, fopen, etc.) against the host
 * operating system's local filesystem. All operations target the single path
 * provided to the constructor.
 *
 * Recursive directory handling is delegated to the CanBeDirectory trait
 * (deep remove and copy), and exclusive-lock writes are provided by the
 * CanBeWritable trait.
 *
 * Assertion preconditions (via Assert) guard write() and append() against being
 * called on directory paths, and files() against being called on plain files.
 * These assertions throw InvalidArgumentException on violation.
 */
class Local implements Storage
{
	use CanBeDirectory;
	use CanBeWritable;

	public function __construct(private string $path) {}

	/**
	 * Creates the file (or directory when $asDirectory is true) at the configured path.
	 *
	 * Ensures the parent directory exists first. Returns false when the node already
	 * exists, true on success.
	 */
	public function create(bool $asDirectory = false): bool
	{
		if (file_exists($this->path)) {
			return false;
		}

		$this->mkdir();

		if ($asDirectory) {
			return mkdir($this->path);
		}

		return touch($this->path);
	}

	/**
	 * Creates the parent directory of the configured path.
	 *
	 * When $recursive is true (the default), all missing intermediate directories
	 * are created. Returns true immediately if the parent directory already exists.
	 */
	public function mkdir(bool $recursive = true): bool
	{
		if (file_exists($this->parentDir())) {
			return true;
		}

		return mkdir(directory: $this->parentDir(), recursive: $recursive);
	}

	/**
	 * Returns true when a file or directory exists at the configured path.
	 */
	public function exists(): bool
	{
		return file_exists($this->path);
	}

	/**
	 * Deletes the node at the configured path. For directories, all children are
	 * removed recursively before the directory itself is deleted.
	 */
	public function remove(): bool
	{
		return $this->removeRecursive($this->path);
	}

	/**
	 * Returns true when the configured path points to a directory.
	 */
	public function isDir(): bool
	{
		return is_dir($this->path);
	}

	/**
	 * Moves the node to $to, creating intermediate parent directories as needed.
	 */
	public function move(string $to): bool
	{
		mkdir(directory: dirname($to), recursive: true);

		return $this->rename($to);
	}

	/**
	 * Copies the node to $to. For directories, the entire subtree is copied
	 * recursively. Throws when the source does not exist.
	 *
	 * @throws InvalidArgumentException
	 */
	public function copy(string $to): bool
	{
		Assert::exists($this->path, 'File Does not Exist');

		return $this->copyRecursive($this->path, $to);
	}

	/**
	 * Returns the absolute path to the parent directory of the configured path.
	 */
	public function parentDir(): string
	{
		return dirname($this->path);
	}

	/**
	 * Renames (moves) the node to $to using PHP's rename(), which works atomically
	 * on most local filesystems when source and destination are on the same device.
	 */
	public function rename(string $to): bool
	{
		return rename($this->path, $to);
	}

	/**
	 * Returns the real paths of all files found recursively under the configured
	 * directory path. Throws when the path is not a directory.
	 *
	 * @return string[]
	 *
	 * @throws InvalidArgumentException
	 */
	public function files(): array
	{
		Assert::dir($this->path, 'File must be a Directory');

		$files = (new FileFinder($this->path))->getFiles();

		$result = [];

		foreach ($files as $file) {
			$result[] = $file->getRealPath();
		}

		return $result;
	}

	/**
	 * Overwrites the file with $data using an exclusive lock.
	 * Throws when the path is a directory.
	 *
	 * @throws InvalidArgumentException
	 */
	public function write(string $data): bool
	{
		Assert::notDir($this->path, 'File could not be a Directory');

		return $this->writeToFile($data, 'w');
	}

	/**
	 * Appends $data to the file using an exclusive lock.
	 * Throws when the path is a directory.
	 *
	 * @throws InvalidArgumentException
	 */
	public function append(string $data): bool
	{
		Assert::notDir($this->path, 'File could not be a Directory');

		return $this->writeToFile($data, 'a');
	}

	/**
	 * Reads the file into an ordered array of lines. Throws when the path does not
	 * exist or is a directory.
	 *
	 * @return false|string[]
	 *
	 * @throws InvalidArgumentException
	 */
	public function readLines(): array|false
	{
		Assert::exists($this->path, 'File Does not Exist');
		Assert::notDir($this->path, 'File could not be a Directory');

		return file($this->path);
	}

	/**
	 * Reads a slice of lines from the file with optional character truncation.
	 *
	 * Returns an associative array with the sliced content string, total line count,
	 * resolved from/to boundaries, and whether the result was truncated by $maxChars.
	 * Returns false when the file cannot be read.
	 *
	 * @param int $from     1-based start line (default: 1)
	 * @param int $to       inclusive end line; 0 = EOF (default: 0)
	 * @param int $maxChars character cap; 0 = unlimited (default: 0)
	 *
	 * @return array{content: string, totalLines: int, from: int, to: int, truncated: bool}|false
	 *
	 * @throws InvalidArgumentException
	 */
	public function readChunk(int $from = 1, int $to = 0, int $maxChars = 0): array|false
	{
		$lines = $this->readLines();

		if (false === $lines) {
			return false;
		}

		$from = max(1, $from);
		$totalLines = count($lines);
		$to = ($to > 0) ? min($to, $totalLines) : $totalLines;

		$lines = array_slice($lines, $from - 1, $to - $from + 1);
		$content = implode(PHP_EOL, $lines);

		$truncated = false;

		if ($maxChars > 0 && mb_strlen($content) > $maxChars) {
			$content = mb_substr($content, 0, $maxChars);
			$truncated = true;
		}

		return [
			'content' => $content,
			'totalLines' => $totalLines,
			'from' => $from,
			'to' => $to,
			'truncated' => $truncated,
		];
	}

	/**
	 * Convenience wrapper around readChunk() that returns just the text content.
	 * Appends a truncation notice when the result was capped by $maxChars.
	 * Returns false when the file cannot be read.
	 *
	 * @param int $from     1-based start line number (default: 1)
	 * @param int $to       inclusive end line; 0 = EOF (default: 0)
	 * @param int $maxChars character cap; 0 = unlimited (default: 0)
	 *
	 * @throws InvalidArgumentException
	 */
	public function readChunkText(int $from = 1, int $to = 0, int $maxChars = 0): false|string
	{
		$chunk = $this->readChunk($from, $to, $maxChars);

		if (false === $chunk) {
			return false;
		}

		$content = $chunk['content'];

		if ($chunk['truncated'] && $maxChars > 0) {
			$content .= "\n[truncated at {$maxChars} chars]";
		}

		return $content;
	}

	/**
	 * Returns the last $limit non-empty lines in reverse order (newest first).
	 *
	 * Useful for reading log files where the most recent entries are at the bottom.
	 * Returns false when the file cannot be read.
	 *
	 * @param int $limit maximum lines to return
	 *
	 * @return false|string[]
	 *
	 * @throws InvalidArgumentException
	 */
	public function readTail(int $limit): array|false
	{
		$lines = $this->readLines();

		if (false === $lines) {
			return false;
		}

		$lines = array_filter($lines);

		return array_values(array_slice(array_reverse($lines), 0, $limit));
	}

	/**
	 * Sets the permission bits on the node. String permissions are converted from
	 * octal notation to an integer before calling chmod().
	 */
	public function setPermission(int|string $permission): bool
	{
		if (is_string($permission)) {
			$permission = (int) base_convert($permission, 8, 10);
		}

		return chmod($this->path, $permission);
	}

	/**
	 * Returns the last four characters of the octal permission string for the node
	 * (e.g. "0644"), as reported by fileperms().
	 */
	public function getPermission(): mixed
	{
		return substr(sprintf('%o', fileperms($this->path)), -4);
	}

	/**
	 * Returns the absolute path this instance was opened with.
	 */
	public function getPath(): string
	{
		return $this->path;
	}
}
