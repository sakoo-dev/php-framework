<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Doc\Stubs;

/**
 * @method static self   make(string $name, int $count = 0) Creates a stub
 * @method        string render(bool $verbose)              Renders the stub
 * @method static string compute()                          @internal hidden method
 */
class DocStubClass
{
	public function __construct(
		private readonly string $name,
		private int $count = 0,
	) {}

	public function getName(): string
	{
		return $this->name;
	}

	public static function create(string $name, int $count = 0): self
	{
		return new self($name, $count);
	}

	protected function protectedMethod(): void {}

	private function privateMethod(): void {}

	public function __toString(): string
	{
		return $this->name;
	}
}
