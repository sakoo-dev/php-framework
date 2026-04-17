<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\FileSystem\Local;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\FileSystem\Permission;

trait HasPermissions
{
	#[Test]
	#[DataProvider('filePermissions')]
	public function it_can_set_permission_on_file($mode, $assertions)
	{
		match (get_class($this)) {
			DirectoryTest::class => $this->createDirectory(),
			FileTest::class => $this->createFile(),
		};

		$this->file->setPermission($mode);
		$this->assertEquals($mode, $this->file->getPermission());

		foreach ($assertions as $assertion) {
			$this->{"assert$assertion"}($this->file->getPath());
		}
	}

	public static function filePermissions(): \Generator
	{
		yield [Permission::allNothing(), ['IsNotExecutable', 'IsNotWritable', 'IsNotReadable']];
		yield [Permission::allExecute(), ['IsExecutable', 'IsNotWritable', 'IsNotReadable']];
		yield [Permission::allWrite(), ['IsNotExecutable', 'IsWritable', 'IsNotReadable']];
		yield [Permission::allExecuteWrite(), ['IsExecutable', 'IsWritable', 'IsNotReadable']];
		yield [Permission::allRead(), ['IsNotExecutable', 'IsNotWritable', 'IsReadable']];
		yield [Permission::allExecuteRead(), ['IsExecutable', 'IsNotWritable', 'IsReadable']];
		yield [Permission::allWriteRead(), ['IsNotExecutable', 'IsWritable', 'IsReadable']];
		yield [Permission::allExecuteWriteRead(), ['IsExecutable', 'IsWritable', 'IsReadable']];
	}
}
