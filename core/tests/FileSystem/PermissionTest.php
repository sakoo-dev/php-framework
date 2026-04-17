<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\FileSystem;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\FileSystem\Permission;
use Sakoo\Framework\Core\Tests\FileSystem\Local\FileSystemTestCase;

final class PermissionTest extends FileSystemTestCase
{
	#[Test]
	#[DataProvider('permissionProvider')]
	#[DataProvider('permissionBatchProvider')]
	public function it_returns_correct_permissions($actual, $method): void
	{
		$this->assertEqualsCanonicalizing($actual, Permission::$method());
	}

	public static function permissionProvider(): \Generator
	{
		yield 'all_nothing' => ['0000', 'allNothing'];
		yield 'all_execute' => ['0111', 'allExecute'];
		yield 'all_write' => ['0222', 'allWrite'];
		yield 'all_execute_write' => ['0333', 'allExecuteWrite'];
		yield 'all_read' => ['0444', 'allRead'];
		yield 'all_execute_read' => ['0555', 'allExecuteRead'];
		yield 'all_write_read' => ['0666', 'allWriteRead'];
		yield 'all_execute_write_read' => ['0777', 'allExecuteWriteRead'];
	}

	public static function permissionBatchProvider(): \Generator
	{
		yield 'executables' => [['0111', '0333', '0555', '0777'], 'getExecutables'];
		yield 'not_executables' => [['0000', '0222', '0444', '0666'], 'getNotExecutables'];
		yield 'writables' => [['0222', '0333', '0666', '0777'], 'getWritables'];
		yield 'not_writables' => [['0000', '0111', '0444', '0555'], 'getNotWritables'];
		yield 'readables' => [['0444', '0555', '0666', '0777'], 'getReadables'];
		yield 'not_readables' => [['0000', '0111', '0222', '0333'], 'getNotReadables'];
	}

	#[Test]
	#[DataProvider('permissionCodeProvider')]
	public function it_returns_correct_permission_codes($actual, $const): void
	{
		$reflection = new \ReflectionClass(Permission::class);
		$this->assertEquals($actual, $reflection->getConstant($const));
	}

	public static function permissionCodeProvider(): \Generator
	{
		yield 'nothing' => ['0', 'NOTHING'];
		yield 'execute' => ['1', 'EXECUTE'];
		yield 'write' => ['2', 'WRITE'];
		yield 'execute_write' => ['3', 'EXECUTE_WRITE'];
		yield 'read' => ['4', 'READ'];
		yield 'execute_read' => ['5', 'EXECUTE_READ'];
		yield 'write_read' => ['6', 'WRITE_READ'];
		yield 'execute_write_read' => ['7', 'EXECUTE_WRITE_READ'];
	}

	#[Test]
	public function it_makes_correct_permissions(): void
	{
		$this->assertEquals('0123', Permission::make(Permission::EXECUTE, Permission::WRITE, Permission::EXECUTE_WRITE));
		$this->assertEquals('0765', Permission::make(7, 6, 5));
	}

	#[Test]
	public function it_returns_file_directory_default_permissions(): void
	{
		$this->assertEquals('0644', Permission::getFileDefault());
		$this->assertEquals('0755', Permission::getDirectoryDefault());
	}
}
