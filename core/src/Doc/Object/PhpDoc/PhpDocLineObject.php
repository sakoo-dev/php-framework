<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\PhpDoc;

readonly class PhpDocLineObject
{
	public function __construct(private string $line) {}

	public function isThrows(): bool
	{
		return str_starts_with($this->line, '@throws ');
	}

	public function isMethod(): bool
	{
		return str_starts_with($this->line, '@method ');
	}

	public function isEmpty(): bool
	{
		return empty($this->line);
	}

	public function __toString(): string
	{
		return $this->line;
	}
}
