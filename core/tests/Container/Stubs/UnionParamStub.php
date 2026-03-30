<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Container\Stubs;

final class UnionParamStub
{
	public function __construct(public readonly int|string $value) {}
}
