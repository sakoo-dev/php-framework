<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Container;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Container\Container;
use Sakoo\Framework\Core\Container\Exceptions\UnresolvableParameterException;
use Sakoo\Framework\Core\Container\Parameter\Parameter;
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
	public function resolve_throws_exception_in_empty_string_for_unbound_string_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [name]');

		$class = new class('') {
			public function __construct(public readonly string $name) {}
		};

		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];
		(new Parameter($this->container))->resolve($param);
	}

	#[Test]
	public function resolve_throws_exception_in_zero_for_unbound_int_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [count]');

		$class = new class(0) {
			public function __construct(public readonly int $count) {}
		};

		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];
		(new Parameter($this->container))->resolve($param);
	}

	#[Test]
	public function resolve_throws_exception_in_zero_float_for_unbound_float_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [value]');

		$class = new class(0.0) {
			public function __construct(public readonly float $value) {}
		};

		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];
		(new Parameter($this->container))->resolve($param);
	}

	#[Test]
	public function resolve_throws_exception_in_false_for_unbound_bool_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [flag]');

		$class = new class(false) {
			public function __construct(public readonly bool $flag) {}
		};

		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];
		(new Parameter($this->container))->resolve($param);
	}

	#[Test]
	public function resolve_throws_exception_in_empty_array_for_unbound_array_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [items]');

		$class = new class([]) {
			public function __construct(public readonly array $items) {}
		};

		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];
		(new Parameter($this->container))->resolve($param);
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
	public function resolve_throws_exception_in_stdclass_for_object_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [obj]');

		$class = new class(new \stdClass()) {
			public function __construct(public readonly object $obj) {}
		};

		$param = (new \ReflectionClass($class))->getConstructor()->getParameters()[0];
		new Parameter($this->container)->resolve($param);
	}

	#[Test]
	public function resolve_throws_exception_in_null_for_untyped_param(): void
	{
		$this->expectException(UnresolvableParameterException::class);
		$this->expectExceptionMessage('Cannot resolve value of Parameter [anything]');

		$params = (new \ReflectionFunction(static function ($anything) {}))->getParameters();
		(new Parameter($this->container))->resolve($params[0]);
	}
}
