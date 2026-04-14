<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 stream implementation wrapping a native PHP stream resource.
 *
 * All read/write/seek operations delegate to the underlying resource. Once
 * detach() or close() is called the stream becomes inert and most operations
 * will throw RuntimeException to signal the unusable state.
 */
final class Stream implements StreamInterface
{
	/** @var null|resource */
	private $resource;

	private ?int $size = null;

	/**
	 * @param resource $resource
	 */
	public function __construct($resource)
	{
		$this->resource = $resource;
	}

	/**
	 * Named constructor that opens a php://temp stream populated with $content.
	 */
	public static function createFromString(string $content = ''): self
	{
		$resource = fopen('php://temp', 'r+');

		if (false === $resource) {
			throw new \RuntimeException('Unable to open php://temp stream.');
		}

		$stream = new self($resource);

		if ('' !== $content) {
			$stream->write($content);
			$stream->rewind();
		}

		return $stream;
	}

	/**
	 * Named constructor that wraps an already-opened PHP resource.
	 *
	 * @param resource $resource
	 */
	public static function create($resource): self
	{
		return new self($resource);
	}

	public function __toString(): string
	{
		if (null === $this->resource) {
			return '';
		}

		try {
			$this->rewind();

			return $this->getContents();
		} catch (\RuntimeException) {
			return '';
		}
	}

	public function close(): void
	{
		if (null !== $this->resource) {
			$resource = $this->detach();

			if (is_resource($resource)) {
				fclose($resource);
			}
		}
	}

	public function detach()
	{
		$resource = $this->resource;
		$this->resource = null;
		$this->size = null;

		return $resource;
	}

	public function getSize(): ?int
	{
		if (null === $this->resource) {
			return null;
		}

		if (null !== $this->size) {
			return $this->size;
		}

		$stats = fstat($this->resource);

		if (false !== $stats) {
			$this->size = $stats['size'];
		}

		return $this->size;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function tell(): int
	{
		$resource = $this->guardDetached();
		$position = ftell($resource);

		if (false === $position) {
			throw new \RuntimeException('Unable to determine stream position.');
		}

		return $position;
	}

	public function eof(): bool
	{
		return null === $this->resource || feof($this->resource);
	}

	public function isSeekable(): bool
	{
		if (null === $this->resource) {
			return false;
		}

		$meta = stream_get_meta_data($this->resource);

		return (bool) $meta['seekable'];
	}

	/**
	 * @throws \RuntimeException
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$resource = $this->guardDetached();

		if (!$this->isSeekable()) {
			throw new \RuntimeException('Stream is not seekable.');
		}

		if (-1 === fseek($resource, $offset, $whence)) {
			throw new \RuntimeException('Unable to seek in stream.');
		}

		$this->size = null;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	public function isWritable(): bool
	{
		if (null === $this->resource) {
			return false;
		}

		$meta = stream_get_meta_data($this->resource);

		return str_contains($meta['mode'], 'w')
			|| str_contains($meta['mode'], 'a')
			|| str_contains($meta['mode'], 'x')
			|| str_contains($meta['mode'], 'c')
			|| str_contains($meta['mode'], '+');
	}

	/**
	 * @throws \RuntimeException
	 */
	public function write(string $string): int
	{
		$resource = $this->guardDetached();

		if (!$this->isWritable()) {
			throw new \RuntimeException('Stream is not writable.');
		}

		$bytes = fwrite($resource, $string);

		if (false === $bytes) {
			throw new \RuntimeException('Unable to write to stream.');
		}

		$this->size = null;

		return $bytes;
	}

	public function isReadable(): bool
	{
		if (null === $this->resource) {
			return false;
		}

		$meta = stream_get_meta_data($this->resource);

		return str_contains($meta['mode'], 'r') || str_contains($meta['mode'], '+');
	}

	/**
	 * @throws \RuntimeException
	 */
	public function read(int $length): string
	{
		$resource = $this->guardDetached();

		if (!$this->isReadable()) {
			throw new \RuntimeException('Stream is not readable.');
		}

		/** @var int<1, max> $length */
		$data = fread($resource, $length);

		if (false === $data) {
			throw new \RuntimeException('Unable to read from stream.');
		}

		return $data;
	}

	/**
	 * @throws \RuntimeException
	 */
	public function getContents(): string
	{
		$resource = $this->guardDetached();

		if (!$this->isReadable()) {
			throw new \RuntimeException('Stream is not readable.');
		}

		$contents = stream_get_contents($resource);

		if (false === $contents) {
			throw new \RuntimeException('Unable to read stream contents.');
		}

		return $contents;
	}

	public function getMetadata(?string $key = null)
	{
		if (null === $this->resource) {
			return null === $key ? [] : null;
		}

		$meta = stream_get_meta_data($this->resource);

		if (null === $key) {
			return $meta;
		}

		return $meta[$key] ?? null;
	}

	/**
	 * Guards that the stream has not been detached and returns the resource.
	 *
	 * @return resource
	 *
	 * @throws \RuntimeException
	 */
	private function guardDetached()
	{
		if (null === $this->resource) {
			throw new \RuntimeException('Stream has been detached.');
		}

		return $this->resource;
	}
}
