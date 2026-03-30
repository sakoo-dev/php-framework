<?php

declare(strict_types=1);

namespace Doc\Object;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Doc\Object\Class\ClassObject;
use Sakoo\Framework\Core\Doc\Object\Method\VirtualMethodObject;
use Sakoo\Framework\Core\Tests\Doc\Stubs\DocStubAbstractClass;
use Sakoo\Framework\Core\Tests\Doc\Stubs\DocStubClass;
use Sakoo\Framework\Core\Tests\Doc\Stubs\DocStubHiddenClass;
use Sakoo\Framework\Core\Tests\Doc\Stubs\DocStubInterface;
use Sakoo\Framework\Core\Tests\TestCase;

final class ClassObjectTest extends TestCase
{
	#[Test]
	public function class_object_returns_name(): void
	{
		$this->assertSame('DocStubClass', (new ClassObject(new \ReflectionClass(DocStubClass::class)))->getName());
	}

	#[Test]
	public function class_object_returns_namespace(): void
	{
		$this->assertSame('Sakoo\Framework\Core\Tests\Doc\Stubs', (new ClassObject(new \ReflectionClass(DocStubClass::class)))->getNamespace());
	}

	#[Test]
	public function class_object_is_instantiable_for_concrete_class(): void
	{
		$this->assertTrue((new ClassObject(new \ReflectionClass(DocStubClass::class)))->isInstantiable());
	}

	#[Test]
	public function class_object_is_not_instantiable_for_abstract_class(): void
	{
		$this->assertFalse((new ClassObject(new \ReflectionClass(DocStubAbstractClass::class)))->isInstantiable());
	}

	#[Test]
	public function class_object_is_not_instantiable_for_interface(): void
	{
		$this->assertFalse((new ClassObject(new \ReflectionClass(DocStubInterface::class)))->isInstantiable());
	}

	#[Test]
	public function class_object_is_illegal_for_abstract_class(): void
	{
		$this->assertTrue((new ClassObject(new \ReflectionClass(DocStubAbstractClass::class)))->isIllegal());
	}

	#[Test]
	public function class_object_is_illegal_for_interface(): void
	{
		$this->assertTrue((new ClassObject(new \ReflectionClass(DocStubInterface::class)))->isIllegal());
	}

	#[Test]
	public function class_object_is_illegal_when_has_dont_document_attribute(): void
	{
		$this->assertTrue((new ClassObject(new \ReflectionClass(DocStubHiddenClass::class)))->isIllegal());
	}

	#[Test]
	public function class_object_is_not_illegal_for_concrete_class(): void
	{
		$this->assertFalse((new ClassObject(new \ReflectionClass(DocStubClass::class)))->isIllegal());
	}

	#[Test]
	public function class_object_should_not_document_when_has_dont_document_attribute(): void
	{
		$this->assertTrue((new ClassObject(new \ReflectionClass(DocStubHiddenClass::class)))->shouldNotDocument());
	}

	#[Test]
	public function class_object_should_document_concrete_class(): void
	{
		$this->assertFalse((new ClassObject(new \ReflectionClass(DocStubClass::class)))->shouldNotDocument());
	}

	#[Test]
	public function class_object_is_not_exception(): void
	{
		$this->assertFalse((new ClassObject(new \ReflectionClass(DocStubClass::class)))->isException());
	}

	#[Test]
	public function class_object_get_methods_returns_array(): void
	{
		$this->assertIsArray((new ClassObject(new \ReflectionClass(DocStubClass::class)))->getMethods());
	}

	#[Test]
	public function class_object_get_virtual_methods_parses_method_tags(): void
	{
		$virtualMethods = (new ClassObject(new \ReflectionClass(DocStubClass::class)))->getVirtualMethods();

		$this->assertGreaterThanOrEqual(2, count($virtualMethods));
		$this->assertContainsOnlyInstancesOf(VirtualMethodObject::class, $virtualMethods);
	}

	#[Test]
	public function class_object_get_interfaces_returns_array(): void
	{
		$this->assertIsArray((new ClassObject(new \ReflectionClass(DocStubClass::class)))->getInterfaces());
	}

	#[Test]
	public function class_object_get_raw_doc_returns_non_empty_string(): void
	{
		$this->assertNotEmpty((new ClassObject(new \ReflectionClass(DocStubClass::class)))->getRawDoc());
	}

	#[Test]
	public function class_object_get_php_doc_object_is_not_null(): void
	{
		$this->assertNotNull((new ClassObject(new \ReflectionClass(DocStubClass::class)))->getPhpDocObject());
	}
}
