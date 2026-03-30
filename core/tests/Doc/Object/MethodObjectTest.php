<?php

declare(strict_types=1);

namespace Doc\Object;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Doc\Object\Class\ClassObject;
use Sakoo\Framework\Core\Doc\Object\Method\MethodObject;
use Sakoo\Framework\Core\Doc\Object\Parameter\ParameterObject;
use Sakoo\Framework\Core\Tests\Doc\Stubs\DocStubClass;
use Sakoo\Framework\Core\Tests\TestCase;

final class MethodObjectTest extends TestCase
{
	private function getMethodObject(string $methodName): MethodObject
	{
		return new MethodObject(
			new ClassObject(new \ReflectionClass(DocStubClass::class)),
			new \ReflectionMethod(DocStubClass::class, $methodName),
		);
	}

	#[Test]
	public function method_object_get_name(): void
	{
		$this->assertSame('getName', $this->getMethodObject('getName')->getName());
	}

	#[Test]
	public function method_object_is_public(): void
	{
		$this->assertTrue($this->getMethodObject('getName')->isPublic());
		$this->assertFalse($this->getMethodObject('getName')->isPrivate());
		$this->assertFalse($this->getMethodObject('getName')->isProtected());
	}

	#[Test]
	public function method_object_is_protected(): void
	{
		$this->assertTrue($this->getMethodObject('protectedMethod')->isProtected());
		$this->assertFalse($this->getMethodObject('protectedMethod')->isPublic());
	}

	#[Test]
	public function method_object_is_private(): void
	{
		$this->assertTrue($this->getMethodObject('privateMethod')->isPrivate());
	}

	#[Test]
	public function method_object_is_static(): void
	{
		$this->assertTrue($this->getMethodObject('create')->isStatic());
		$this->assertFalse($this->getMethodObject('getName')->isStatic());
	}

	#[Test]
	public function method_object_is_constructor(): void
	{
		$this->assertTrue($this->getMethodObject('__construct')->isConstructor());
		$this->assertFalse($this->getMethodObject('getName')->isConstructor());
	}

	#[Test]
	public function method_object_is_magic_method(): void
	{
		$this->assertTrue($this->getMethodObject('__toString')->isMagicMethod());
		$this->assertTrue($this->getMethodObject('__construct')->isMagicMethod());
		$this->assertFalse($this->getMethodObject('getName')->isMagicMethod());
	}

	#[Test]
	public function method_object_should_not_document_private_method(): void
	{
		$this->assertTrue($this->getMethodObject('privateMethod')->shouldNotDocument());
	}

	#[Test]
	public function method_object_should_not_document_non_constructor_magic_method(): void
	{
		$this->assertTrue($this->getMethodObject('__toString')->shouldNotDocument());
	}

	#[Test]
	public function method_object_constructor_should_document(): void
	{
		$this->assertFalse($this->getMethodObject('__construct')->shouldNotDocument());
	}

	#[Test]
	public function method_object_public_method_should_document(): void
	{
		$this->assertFalse($this->getMethodObject('getName')->shouldNotDocument());
	}

	#[Test]
	public function method_object_get_class_returns_class_object(): void
	{
		$this->assertInstanceOf(ClassObject::class, $this->getMethodObject('getName')->getClass());
	}

	#[Test]
	public function method_object_get_method_return_types_for_string_return(): void
	{
		$this->assertSame('string', $this->getMethodObject('getName')->getMethodReturnTypes());
	}

	#[Test]
	public function method_object_get_method_return_types_for_void(): void
	{
		$this->assertSame('void', $this->getMethodObject('protectedMethod')->getMethodReturnTypes());
	}

	#[Test]
	public function method_object_get_method_parameters_for_constructor(): void
	{
		$params = $this->getMethodObject('__construct')->getMethodParameters();

		$this->assertCount(2, $params);
		$this->assertContainsOnlyInstancesOf(ParameterObject::class, $params);
	}

	#[Test]
	public function method_object_get_default_values(): void
	{
		$this->assertSame('$name, $count', $this->getMethodObject('create')->getDefaultValues());
	}

	#[Test]
	public function method_object_get_default_value_types(): void
	{
		$this->assertSame('string $name, int $count', $this->getMethodObject('create')->getDefaultValueTypes());
	}

	#[Test]
	public function method_object_get_modifiers(): void
	{
		$this->assertContains('public', $this->getMethodObject('getName')->getModifiers());
	}

	#[Test]
	public function method_object_is_framework_function_for_stub_in_core_namespace(): void
	{
		$this->assertTrue($this->getMethodObject('getName')->isFrameworkFunction());
	}

	#[Test]
	public function method_object_get_raw_doc_returns_string(): void
	{
		$this->assertIsString($this->getMethodObject('getName')->getRawDoc());
	}

	#[Test]
	public function method_object_get_php_doc_object_is_not_null(): void
	{
		$this->assertNotNull($this->getMethodObject('getName')->getPhpDocObject());
	}
}
