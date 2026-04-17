<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Assert;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use Sakoo\Framework\Core\FileSystem\Permission;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Str\Str;
use Sakoo\Framework\Core\Tests\TestCase;

final class AssertTest extends TestCase
{
	use Helpers;

	#[Test]
	#[DataProvider('typeProvider')]
	public function it_type_assertions_work_properly($function, $valid, $invalid, $message): void
	{
		foreach ($valid as $item) {
			Assert::$function($item);
		}

		foreach ($invalid as $item) {
			$this->throwsException(fn () => Assert::$function($item))
				->withType(InvalidArgumentException::class)
				->withMessage(sprintf($message, Str::fromType($item)))
				->validate();
		}
	}

	public static function typeProvider(): \Generator
	{
		yield 'true' => [
			'function' => 'true',
			'valid' => [true],
			'invalid' => [false, 0, 1, 'true'],
			'message' => 'Given value %s is not true',
		];
		yield 'false' => [
			'function' => 'false',
			'valid' => [false],
			'invalid' => [true, 0, 1, 'false'],
			'message' => 'Given value %s is not false',
		];
		yield 'bool' => [
			'function' => 'bool',
			'valid' => [true, false],
			'invalid' => [0, 1, 'true', 'false', null],
			'message' => 'Given value %s is not boolean',
		];
		yield 'notBool' => [
			'function' => 'notBool',
			'valid' => [0, 1, 'true', 'false', null],
			'invalid' => [true, false],
			'message' => 'Given value %s is boolean',
		];
		yield 'callable' => [
			'function' => 'callable',
			'valid' => [fn () => '', function () {}],
			'invalid' => ['', null, 1],
			'message' => 'Given value %s is not callable',
		];
		yield 'notCallable' => [
			'function' => 'notCallable',
			'valid' => ['', null, 1],
			'invalid' => [fn () => '', function () {}],
			'message' => 'Given value %s is callable',
		];
		yield 'finite' => [
			'function' => 'finite',
			'valid' => [10 / 2],
			'invalid' => [log(0)],
			'message' => 'Given value %s is an infinite number',
		];
		yield 'infinite' => [
			'function' => 'infinite',
			'valid' => [log(0)],
			'invalid' => [10 / 2],
			'message' => 'Given value %s is a finite number',
		];
		yield 'dir' => [
			'function' => 'dir',
			'valid' => [Path::getCoreDir(), __DIR__],
			'invalid' => ['', '/not-exist/', __FILE__],
			'message' => 'Given value %s is not a directory',
		];
		yield 'notDir' => [
			'function' => 'notDir',
			'valid' => ['', '/not-exist/', __FILE__],
			'invalid' => [Path::getCoreDir(), __DIR__],
			'message' => 'Given value %s is a directory',
		];
		yield 'file' => [
			'function' => 'file',
			'valid' => [__FILE__],
			'invalid' => [Path::getCoreDir(), __DIR__, '', 'not-exists.txt'],
			'message' => 'Given value %s is not a file',
		];
		yield 'notFile' => [
			'function' => 'notFile',
			'valid' => [Path::getCoreDir(), __DIR__, '', 'not-exists.txt'],
			'invalid' => [__FILE__],
			'message' => 'Given value %s is a file',
		];
		yield 'null' => [
			'function' => 'null',
			'valid' => [null],
			'invalid' => [true, false, 0, '', []],
			'message' => 'Given value %s is not null',
		];
		yield 'notNull' => [
			'function' => 'notNull',
			'valid' => [true, false, 0, '', []],
			'invalid' => [null],
			'message' => 'Given value %s is null',
		];
		yield 'numeric' => [
			'function' => 'numeric',
			'valid' => [0, 1, 3.14],
			'invalid' => ['', null, [], false],
			'message' => 'Given value %s is not numeric',
		];
		yield 'notNumeric' => [
			'function' => 'notNumeric',
			'valid' => ['', null, [], false],
			'invalid' => [0, 1, 3.14],
			'message' => 'Given value %s is numeric',
		];
		yield 'float' => [
			'function' => 'float',
			'valid' => [1.0, 3.14, 0.5],
			'invalid' => [0, 1, 2],
			'message' => 'Given value %s is not a float number',
		];
		yield 'notFloat' => [
			'function' => 'notFloat',
			'valid' => [0, 1, 2],
			'invalid' => [1.0, 3.14, 0.5],
			'message' => 'Given value %s is a float number',
		];
		yield 'int' => [
			'function' => 'int',
			'valid' => [1, 2],
			'invalid' => [1.0, 0.5],
			'message' => 'Given value %s is not an integer number',
		];
		yield 'notInt' => [
			'function' => 'notInt',
			'valid' => [1.0, 0.5],
			'invalid' => [1, 2],
			'message' => 'Given value %s is an integer number',
		];
		yield 'object' => [
			'function' => 'object',
			'valid' => [new \stdClass()],
			'invalid' => [\stdClass::class, '', null, true],
			'message' => 'Given value %s is not an object',
		];
		yield 'notObject' => [
			'function' => 'notObject',
			'valid' => [\stdClass::class, '', null, true],
			'invalid' => [new \stdClass()],
			'message' => 'Given value %s is an object',
		];
		yield 'resource' => [
			'function' => 'resource',
			'valid' => [fopen('php://stdout', 'w')],
			'invalid' => [''],
			'message' => 'Given value %s is not a resource',
		];
		yield 'notResource' => [
			'function' => 'notResource',
			'valid' => [''],
			'invalid' => [fopen('php://stdout', 'w')],
			'message' => 'Given value %s is a resource',
		];
		yield 'array' => [
			'function' => 'array',
			'valid' => [[]],
			'invalid' => [''],
			'message' => 'Given value %s is not an array',
		];
		yield 'notArray' => [
			'function' => 'notArray',
			'valid' => [''],
			'invalid' => [[]],
			'message' => 'Given value %s is an array',
		];
		yield 'countable' => [
			'function' => 'countable',
			'valid' => [[]],
			'invalid' => [''],
			'message' => 'Given value %s is not countable',
		];
		yield 'notCountable' => [
			'function' => 'notCountable',
			'valid' => [''],
			'invalid' => [[]],
			'message' => 'Given value %s is countable',
		];
		yield 'iterable' => [
			'function' => 'iterable',
			'valid' => [[]],
			'invalid' => [''],
			'message' => 'Given value %s is not iterable',
		];
		yield 'notIterable' => [
			'function' => 'notIterable',
			'valid' => [''],
			'invalid' => [[]],
			'message' => 'Given value %s is iterable',
		];
		yield 'scalar' => [
			'function' => 'scalar',
			'valid' => [1, '', true],
			'invalid' => [null, new \stdClass()],
			'message' => 'Given value %s is not scalar',
		];
		yield 'notScalar' => [
			'function' => 'notScalar',
			'valid' => [null, new \stdClass()],
			'invalid' => [1, '', true],
			'message' => 'Given value %s is scalar',
		];
		yield 'string' => [
			'function' => 'string',
			'valid' => ['Something'],
			'invalid' => [1, null, []],
			'message' => 'Given value %s is not string',
		];
		yield 'notString' => [
			'function' => 'notString',
			'valid' => [1, null, []],
			'invalid' => ['Something'],
			'message' => 'Given value %s is string',
		];
		yield 'empty' => [
			'function' => 'empty',
			'valid' => [[], '', 0, null, false],
			'invalid' => [['item'], 'Something', 1, true],
			'message' => 'Given value %s is not empty',
		];
		yield 'notEmpty' => [
			'function' => 'notEmpty',
			'valid' => [['item'], 'Something', 1, true],
			'invalid' => [[], '', 0, null, false],
			'message' => 'Given value %s is empty',
		];
		yield 'exists' => [
			'function' => 'exists',
			'valid' => [__FILE__, __DIR__],
			'invalid' => [Path::getTempTestDir() . 'not-exist-file'],
			'message' => 'Given value %s is not an exists file',
		];
		yield 'notExists' => [
			'function' => 'notExists',
			'valid' => [Path::getTempTestDir() . 'not-exist-file'],
			'invalid' => [__FILE__, __DIR__],
			'message' => 'Given value %s is an exists file',
		];
	}

	#[Test]
	#[DataProvider('complexProvider')]
	public function it_complex_assertions_work_properly($function, $valid, $invalid, $message): void
	{
		foreach ($valid as $item) {
			Assert::$function(...$item);
		}

		foreach ($invalid as $item) {
			$this->throwsException(fn () => Assert::$function(...$item))
				->withType(InvalidArgumentException::class)
				->withMessage(sprintf(
					$message,
					Str::fromType($item[0]),
					Str::fromType($item[1]),
				))->validate();
		}
	}

	public static function complexProvider(): \Generator
	{
		yield 'instanceOf' => [
			'function' => 'instanceOf',
			'valid' => [[self::class, TestCase::class]],
			'invalid' => [[self::class, Exception::class]],
			'message' => 'Given value %s is not instance of %s',
		];
		yield 'notInstanceOf' => [
			'function' => 'notInstanceOf',
			'valid' => [[self::class, Exception::class]],
			'invalid' => [[self::class, TestCase::class]],
			'message' => 'Given value %s is instance of %s',
		];
		yield 'equals' => [
			'function' => 'equals',
			'valid' => [[3, '3']],
			'invalid' => [[4, '3']],
			'message' => 'Given value %s is not equals to %s',
		];
		yield 'notEquals' => [
			'function' => 'notEquals',
			'valid' => [[4, '3']],
			'invalid' => [[3, '3']],
			'message' => 'Given value %s is equals to %s',
		];
		yield 'same' => [
			'function' => 'same',
			'valid' => [[3, 3]],
			'invalid' => [[3, '3']],
			'message' => 'Given value %s is not same to %s',
		];
		yield 'notSame' => [
			'function' => 'notSame',
			'valid' => [[3, '3']],
			'invalid' => [[3, 3]],
			'message' => 'Given value %s is same to %s',
		];
		yield 'greater' => [
			'function' => 'greater',
			'valid' => [[2, 1], [5, 3]],
			'invalid' => [[1, 2], [3, 5]],
			'message' => 'Given value %s is not greater than %s',
		];
		yield 'greaterOrEquals' => [
			'function' => 'greaterOrEquals',
			'valid' => [[2, 1], [5, 3], [2, 2]],
			'invalid' => [[1, 2]],
			'message' => 'Given value %s is not greater or equals to %s',
		];
		yield 'lower' => [
			'function' => 'lower',
			'valid' => [[1, 2], [0, 1]],
			'invalid' => [[2, 1], [1, 0]],
			'message' => 'Given value %s is not lower than %s',
		];
		yield 'lowerOrEquals' => [
			'function' => 'lowerOrEquals',
			'valid' => [[1, 2], [3, 5], [2, 2]],
			'invalid' => [[2, 1], [5, 3]],
			'message' => 'Given value %s is not lower or equals to %s',
		];
	}

	#[Test]
	public function it_length_assertion_works_properly(): void
	{
		$valid = [['ABCDEF', 6]];
		$invalid = [['ABCDEF', 7]];

		foreach ($valid as $item) {
			Assert::length(...$item);
		}

		foreach ($invalid as $item) {
			$this->throwsException(fn () => Assert::length(...$item))
				->withType(InvalidArgumentException::class)
				->withMessage(sprintf(
					'The length of %s is %s, Expected %s',
					Str::fromType($item[0]),
					Str::fromType(strlen($item[0])),
					Str::fromType($item[1]),
				))->validate();
		}
	}

	#[Test]
	public function it_count_assertion_works_properly(): void
	{
		$valid = [[['A', 'B', 'C'], 3]];
		$invalid = [[['A', 'B', 'C'], 2]];

		foreach ($valid as $item) {
			Assert::count(...$item);
		}

		foreach ($invalid as $item) {
			$this->throwsException(fn () => Assert::count(...$item))
				->withType(InvalidArgumentException::class)
				->withMessage(sprintf(
					'The count of %s is %s, Expected %s',
					Str::fromType($item[0]),
					Str::fromType(count($item[0])),
					Str::fromType($item[1]),
				))->validate();
		}
	}

	#[Test]
	#[DataProvider('permissionProvider')]
	public function it_permission_assertions_work_properly($function, $valid, $invalid, $message): void
	{
		$file = $this->makeTemporaryFile();

		foreach ($valid as $item) {
			$file->setPermission($item);
			Assert::$function($file->getPath());
		}

		foreach ($invalid as $item) {
			$this->throwsException(fn () => $file->setPermission($item) && Assert::$function($file->getPath()))
				->withType(InvalidArgumentException::class)
				->withMessage(sprintf($message, Str::fromType($file->getPath())))
				->validate();
		}

		$file->remove();
	}

	public static function permissionProvider(): \Generator
	{
		yield 'executableFile' => [
			'function' => 'executableFile',
			'valid' => Permission::getExecutables(),
			'invalid' => Permission::getNotExecutables(),
			'message' => 'Given value %s is not a executable file',
		];
		yield 'notExecutableFile' => [
			'function' => 'notExecutableFile',
			'valid' => Permission::getNotExecutables(),
			'invalid' => Permission::getExecutables(),
			'message' => 'Given value %s is a executable file',
		];
		yield 'writableFile' => [
			'function' => 'writableFile',
			'valid' => Permission::getWritables(),
			'invalid' => Permission::getNotWritables(),
			'message' => 'Given value %s is not a writable file',
		];
		yield 'notWritableFile' => [
			'function' => 'notWritableFile',
			'valid' => Permission::getNotWritables(),
			'invalid' => Permission::getWritables(),
			'message' => 'Given value %s is a writable file',
		];
		yield 'readableFile' => [
			'function' => 'readableFile',
			'valid' => Permission::getReadables(),
			'invalid' => Permission::getNotReadables(),
			'message' => 'Given value %s is not a readable file',
		];
		yield 'notReadableFile' => [
			'function' => 'notReadableFile',
			'valid' => Permission::getNotReadables(),
			'invalid' => Permission::getReadables(),
			'message' => 'Given value %s is a readable file',
		];
	}

	#[Test]
	public function it_symlink_assertions_work_properly(): void
	{
		$symlink = $this->makeTemporarySymlink();

		Assert::link($symlink);

		$this->throwsException(fn () => Assert::link(__FILE__))
			->withType(InvalidArgumentException::class)
			->withMessage('Given value ' . __FILE__ . ' is not a link')
			->validate();

		Assert::notLink(__FILE__);

		$this->throwsException(fn () => Assert::notLink($symlink))
			->withType(InvalidArgumentException::class)
			->withMessage("Given value $symlink is a link")
			->validate();

		unlink($symlink);
	}

	#[Test]
	public function it_upload_file_assertions_work_properly(): void
	{
		$file = $this->makeTemporaryFile();

		$this->throwsException(fn () => Assert::uploadedFile($file->getPath()))
			->withType(InvalidArgumentException::class)
			->withMessage('Given value ' . $file->getPath() . ' is not a uploaded file')
			->validate();

		Assert::notUploadedFile($file->getPath());

		$file->remove();
	}
}
