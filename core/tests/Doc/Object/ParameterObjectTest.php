<?php

declare(strict_types=1);

namespace Doc\Object;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Doc\Object\Parameter\ParameterObject;
use Sakoo\Framework\Core\Doc\Object\Parameter\TypeObject;
use Sakoo\Framework\Core\Tests\TestCase;

final class ParameterObjectTest extends TestCase
{
	#[Test]
	public function parameter_object_get_name(): void
	{
		$reflParam = (new \ReflectionFunction(function (string $myParam) {}))->getParameters()[0];

		$this->assertSame('myParam', (new ParameterObject($reflParam))->getName());
	}

	#[Test]
	public function parameter_object_get_type_returns_type_object(): void
	{
		$reflParam = (new \ReflectionFunction(function (int $count) {}))->getParameters()[0];
		$obj = new ParameterObject($reflParam);

		$this->assertInstanceOf(TypeObject::class, $obj->getType());
		$this->assertSame('int', $obj->getType()->getName());
	}

	#[Test]
	public function parameter_object_get_type_for_untyped_param(): void
	{
		$reflParam = (new \ReflectionFunction(function ($anything) {}))->getParameters()[0];

		$this->assertNull((new ParameterObject($reflParam))->getType()->getName());
	}
}
