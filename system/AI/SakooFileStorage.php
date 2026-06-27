<?php

declare(strict_types=1);

namespace System\AI;

use Sakoo\AI\Neuron\FileSystem\FileStorageInterface;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;

/**
 * sakoo/core adapter for FileStorageInterface.
 *
 * Use this in Sakoo applications to keep using the framework's File/Disk
 * abstraction while the AI package stays decoupled from it.
 *
 * Bind this in AIServiceLoader when sakoo/core is available:
 *
 *   $container->singleton(FileStorageInterface::class, new SakooFileStorage());
 */
final class SakooFileStorage implements FileStorageInterface
{
	public function exists(string $path): bool
	{
		$file = File::open(Disk::Local, $path);

		return $file->exists() && !$file->isDir();
	}

	public function read(string $path): string
	{
		$file = File::open(Disk::Local, $path);

		if (!$file->exists() || $file->isDir()) {
			return '';
		}

		return implode(PHP_EOL, $file->readLines());
	}

	public function write(string $path, string $content): bool
	{
		return (bool) File::open(Disk::Local, $path)->write($content);
	}

	public function append(string $path, string $content): void
	{
		$file = File::open(Disk::Local, $path);
		$file->create();
		$file->append($content);
	}

	public function create(string $path): bool
	{
		return (bool) File::open(Disk::Local, $path)->create();
	}

	public function delete(string $path): void
	{
		File::open(Disk::Local, $path)->remove();
	}
}
