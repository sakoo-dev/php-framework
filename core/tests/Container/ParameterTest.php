<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Container;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Parameter\Parameter;
use Sakoo\Framework\Core\Tests\Container\Stubs\UnionParamStub;
use Sakoo\Framework\Core\Tests\TestCase;

final class ParameterTest extends TestCase
{
	private Container $container;

	protected function setUp(): void
	{
		parent::setUp();
		$this->container = new Container();
	}

	#[Test]
	public function resolve_produces_empty_string_for_unbound_string_param(): void
	{
		$class = new class('') {
			public function __construct(public readonly string $name) {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertSame('', (new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_produces_zero_for_unbound_int_param(): void
	{
		$class = new class(0) {
			public function __construct(public readonly int $count) {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertSame(0, (new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_produces_zero_float_for_unbound_float_param(): void
	{
		$class = new class(0.0) {
			public function __construct(public readonly float $value) {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertSame(0.0, (new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_produces_false_for_unbound_bool_param(): void
	{
		$class = new class(false) {
			public function __construct(public readonly bool $flag) {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertFalse((new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_produces_empty_array_for_unbound_array_param(): void
	{
		$class = new class([]) {
			public function __construct(public readonly array $items) {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertSame([], (new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_uses_default_value_when_available(): void
	{
		$class = new class('hello') {
			public function __construct(public readonly string $name = 'hello') {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertSame('hello', (new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_produces_stdclass_for_object_param(): void
	{
		$class = new class(new \stdClass()) {
			public function __construct(public readonly object $obj) {}
		};
		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];

		$this->assertInstanceOf(\stdClass::class, (new Parameter($this->container))->resolve($param));
	}

	#[Test]
	public function resolve_produces_null_for_untyped_param(): void
	{
		$params = (new \ReflectionFunction(static function ($anything) {}))->getParameters();

		$this->assertNull((new Parameter($this->container))->resolve($params[0]));
	}

	#[Test]
	public function generate_default_value_handles_union_type_with_builtins(): void
	{
		$parameter = new Parameter($this->container);
		$refMethod = new \ReflectionMethod(Parameter::class, 'generateDefaultValue');
		$refMethod->setAccessible(true);

		$unionType = (new \ReflectionClass(UnionParamStub::class))->getConstructor()->getParameters()[0]->getType();
		$result = $refMethod->invoke($parameter, $unionType);

		$this->assertTrue(is_int($result) || is_string($result));
	}

	#[Test]
	public function generate_default_value_returns_null_for_null_type(): void
	{
		$refMethod = new \ReflectionMethod(Parameter::class, 'generateDefaultValue');
		$refMethod->setAccessible(true);

		$this->assertNull($refMethod->invoke(new Parameter($this->container), null));
	}
}
