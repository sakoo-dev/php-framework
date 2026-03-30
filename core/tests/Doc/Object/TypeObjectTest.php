<?php

declare(strict_types=1);

namespace Doc\Object;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Doc\Object\Parameter\TypeObject;
use Sakoo\Framework\Core\Tests\TestCase;

final class TypeObjectTest extends TestCase
{
	#[Test]
	public function type_object_returns_null_for_null_type(): void
	{
		$this->assertNull((new TypeObject(null))->getName());
	}

	#[Test]
	public function type_object_returns_builtin_type_name(): void
	{
		$param = (new \ReflectionFunction(function (string $x) {}))->getParameters()[0];

		$this->assertSame('string', (new TypeObject($param->getType()))->getName());
	}

	#[Test]
	public function type_object_returns_short_class_name_for_non_builtin(): void
	{
		$param = (new \ReflectionFunction(function (\stdClass $x) {}))->getParameters()[0];

		$this->assertSame('stdClass', (new TypeObject($param->getType()))->getName());
	}

	#[Test]
	public function type_object_handles_union_type(): void
	{
		$result = eval('return new class { public function foo(int|string $x): void {} };');
		$param = (new \ReflectionClass($result))->getMethod('foo')->getParameters()[0];
		$name = (new TypeObject($param->getType()))->getName();

		$this->assertStringContainsString('int', $name);
		$this->assertStringContainsString('string', $name);
		$this->assertStringContainsString('|', $name);
	}
}
