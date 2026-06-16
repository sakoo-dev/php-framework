<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Finder\DTO;

/**
 * File system metadata for a single file.
 *
 * Immutable value object containing size, modification time, and permission
 * information. Returned by FileSearch::metadata().
 */
final readonly class FileMetadata
{
	/**
	 * @param string $path relative path to the file
	 * @param int $size file size in bytes
	 * @param int $modified Unix timestamp of last modification
	 * @param string $permissions octal permission string (e.g. '0644')
	 * @param bool $readable whether the file is readable by current user
	 * @param bool $writable whether the file is writable by current user
	 */
	public function __construct(
		public string $path,
		public int $size,
		public int $modified,
		public string $permissions,
		public bool $readable,
		public bool $writable,
	) {}

	/**
	 * @return array{path: string, size: int, modified: int, permissions: string, readable: bool, writable: bool}
	 */
	public function toArray(): array
	{
		return [
			'path' => $this->path,
			'size' => $this->size,
			'modified' => $this->modified,
			'permissions' => $this->permissions,
			'readable' => $this->readable,
			'writable' => $this->writable,
		];
	}
}
