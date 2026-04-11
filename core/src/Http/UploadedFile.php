<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Immutable PSR-7 uploaded file value object.
 *
 * Works in both SAPI (move_uploaded_file) and non-SAPI (stream copy)
 * environments. Once moveTo() has been called the file is consumed and
 * further calls to moveTo() or getStream() will throw RuntimeException.
 */
class UploadedFile implements UploadedFileInterface
{
	private bool $moved = false;

	public function __construct(
		private readonly StreamInterface $stream,
		private readonly ?int $size = null,
		private readonly int $error = UPLOAD_ERR_OK,
		private readonly ?string $clientFilename = null,
		private readonly ?string $clientMediaType = null,
	) {}

	/**
	 * @throws \RuntimeException
	 */
	public function getStream(): StreamInterface
	{
		$this->guardMoved();
		$this->guardUploadError();

		return $this->stream;
	}

	/**
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function moveTo(string $targetPath): void
	{
		$this->guardMoved();
		$this->guardUploadError();

		if ('' === $targetPath) {
			throw new \InvalidArgumentException('Target path must be a non-empty string.');
		}

		$directory = dirname($targetPath);

		if (!is_dir($directory) || !is_writable($directory)) {
			throw new \RuntimeException("Target directory is not writable: $directory");
		}

		$target = fopen($targetPath, 'w');

		if (false === $target) {
			throw new \RuntimeException("Unable to open target path: $targetPath");
		}

		$this->stream->rewind();

		while (!$this->stream->eof()) {
			fwrite($target, $this->stream->read(8192));
		}

		fclose($target);

		$this->moved = true;
	}

	public function getSize(): ?int
	{
		return $this->size;
	}

	public function getError(): int
	{
		return $this->error;
	}

	public function getClientFilename(): ?string
	{
		return $this->clientFilename;
	}

	public function getClientMediaType(): ?string
	{
		return $this->clientMediaType;
	}

	/**
	 * @throws \RuntimeException
	 */
	private function guardMoved(): void
	{
		if ($this->moved) {
			throw new \RuntimeException('Uploaded file has already been moved.');
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	private function guardUploadError(): void
	{
		if (UPLOAD_ERR_OK !== $this->error) {
			throw new \RuntimeException("Upload error code: $this->error");
		}
	}
}
