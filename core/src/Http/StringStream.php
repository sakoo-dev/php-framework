<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Read-only in-memory stream backed by a plain PHP string.
 *
 * Unlike Stream (which opens a php://temp resource for every body),
 * StringStream performs zero syscalls and zero file-descriptor allocation.
 * It is the preferred body carrier for all response objects whose content
 * is already fully materialised as a string.
 */
final class StringStream implements StreamInterface
{
	private int $position = 0;

	public function __construct(private readonly string $content = '') {}

	public static function of(string $content = ''): self
	{
		return new self($content);
	}

	public function __toString(): string
	{
		return $this->content;
	}

	public function close(): void {}

	public function detach(): mixed
	{
		return null;
	}

	public function getSize(): int
	{
		return strlen($this->content);
	}

	public function tell(): int
	{
		return $this->position;
	}

	public function eof(): bool
	{
		return $this->position >= strlen($this->content);
	}

	public function isSeekable(): bool
	{
		return true;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$length = strlen($this->content);

		$this->position = match ($whence) {
			SEEK_SET => $offset,
			SEEK_CUR => $this->position + $offset,
			SEEK_END => $length + $offset,
			default => throw new \RuntimeException("Invalid whence: $whence"),
		};

		if ($this->position < 0) {
			$this->position = 0;
		}

		if ($this->position > $length) {
			$this->position = $length;
		}
	}

	public function rewind(): void
	{
		$this->position = 0;
	}

	public function isWritable(): bool
	{
		return false;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function write(string $string): int
	{
		throw new \RuntimeException('StringStream is read-only.');
	}

	public function isReadable(): bool
	{
		return true;
	}

	public function read(int $length): string
	{
		$chunk = substr($this->content, $this->position, $length);
		$this->position += strlen($chunk);

		return $chunk;
	}

	public function getContents(): string
	{
		$contents = substr($this->content, $this->position);
		$this->position = strlen($this->content);

		return $contents;
	}

	public function getMetadata(?string $key = null): mixed
	{
		return null === $key ? [] : null;
	}
}
