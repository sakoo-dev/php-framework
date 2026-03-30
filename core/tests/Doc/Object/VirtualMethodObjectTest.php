<?php

declare(strict_types=1);

namespace Doc\Object;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Doc\Object\Class\ClassObject;
use Sakoo\Framework\Core\Doc\Object\Method\InvalidVirtualMethodDefinitionException;
use Sakoo\Framework\Core\Doc\Object\Method\VirtualMethodObject;
use Sakoo\Framework\Core\Tests\Doc\Stubs\DocStubClass;
use Sakoo\Framework\Core\Tests\TestCase;

final class VirtualMethodObjectTest extends TestCase
{
	private function makeVirtualMethod(string $line): VirtualMethodObject
	{
		return new VirtualMethodObject(new ClassObject(new \ReflectionClass(DocStubClass::class)), $line);
	}

	#[Test]
	public function virtual_method_object_parses_simple_method(): void
	{
		$obj = $this->makeVirtualMethod('@method string render(bool $verbose) Renders something');

		$this->assertSame('render', $obj->getName());
		$this->assertSame('string', $obj->getMethodReturnTypes());
		$this->assertTrue($obj->isPublic());
		$this->assertFalse($obj->isPrivate());
		$this->assertFalse($obj->isProtected());
		$this->assertFalse($obj->isStatic());
		$this->assertFalse($obj->isConstructor());
		$this->assertFalse($obj->isMagicMethod());
	}

	#[Test]
	public function virtual_method_object_parses_static_method(): void
	{
		$obj = $this->makeVirtualMethod('@method static self make(string $name)');

		$this->assertSame('make', $obj->getName());
		$this->assertTrue($obj->isStatic());
		$this->assertSame('self', $obj->getMethodReturnTypes());
	}

	#[Test]
	public function virtual_method_object_parses_method_without_return_type(): void
	{
		$obj = $this->makeVirtualMethod('@method doThing()');

		$this->assertSame('doThing', $obj->getName());
		$this->assertNull($obj->getMethodReturnTypes());
		$this->assertFalse($obj->isStatic());
	}

	#[Test]
	public function virtual_method_object_is_constructor_when_name_is_construct(): void
	{
		$obj = $this->makeVirtualMethod('@method __construct(string $name)');

		$this->assertTrue($obj->isConstructor());
		$this->assertTrue($obj->isMagicMethod());
	}

	#[Test]
	public function virtual_method_object_should_not_document_when_internal(): void
	{
		$this->assertTrue($this->makeVirtualMethod('@method static string compute() @internal hidden method')->shouldNotDocument());
	}

	#[Test]
	public function virtual_method_object_should_document_regular_method(): void
	{
		$this->assertFalse($this->makeVirtualMethod('@method string render()')->shouldNotDocument());
	}

	#[Test]
	public function virtual_method_object_is_not_framework_function(): void
	{
		$this->assertFalse($this->makeVirtualMethod('@method string foo()')->isFrameworkFunction());
	}

	#[Test]
	public function virtual_method_object_get_class_returns_class_interface(): void
	{
		$classObj = new ClassObject(new \ReflectionClass(DocStubClass::class));

		$this->assertSame($classObj->getName(), $this->makeVirtualMethod('@method string foo()')->getClass()->getName());
	}

	#[Test]
	public function virtual_method_object_get_modifiers_for_non_static(): void
	{
		$this->assertSame(['public'], $this->makeVirtualMethod('@method string foo()')->getModifiers());
	}

	#[Test]
	public function virtual_method_object_get_modifiers_for_static(): void
	{
		$this->assertSame(['public', 'static'], $this->makeVirtualMethod('@method static string foo()')->getModifiers());
	}

	#[Test]
	public function virtual_method_object_get_default_value_types(): void
	{
		$types = $this->makeVirtualMethod('@method string render(bool $verbose, string $format)')->getDefaultValueTypes();

		$this->assertStringContainsString('bool', $types);
		$this->assertStringContainsString('string', $types);
	}

	#[Test]
	public function virtual_method_object_is_static_instantiator_for_static_self_return(): void
	{
		$this->assertTrue($this->makeVirtualMethod('@method static self make(string $name)')->isStaticInstantiator());
	}

	#[Test]
	public function virtual_method_object_is_not_static_instantiator_for_non_static(): void
	{
		$this->assertFalse($this->makeVirtualMethod('@method string render()')->isStaticInstantiator());
	}

	#[Test]
	public function virtual_method_object_throws_on_missing_opening_paren(): void
	{
		$this->expectException(InvalidVirtualMethodDefinitionException::class);

		$this->makeVirtualMethod('@method string render');
	}

	#[Test]
	public function virtual_method_object_throws_on_missing_closing_paren(): void
	{
		$this->expectException(InvalidVirtualMethodDefinitionException::class);

		$this->makeVirtualMethod('@method string render(bool $verbose');
	}

	#[Test]
	public function virtual_method_object_get_raw_doc_returns_description(): void
	{
		$this->assertSame('My description', $this->makeVirtualMethod('@method string render() My description')->getRawDoc());
	}

	#[Test]
	public function virtual_method_object_get_raw_doc_empty_when_no_description(): void
	{
		$this->assertSame('', $this->makeVirtualMethod('@method string render()')->getRawDoc());
	}
}
