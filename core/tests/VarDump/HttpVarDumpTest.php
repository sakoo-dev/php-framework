<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\VarDump;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sakoo\Framework\Core\VarDump\Formatter;
use Sakoo\Framework\Core\VarDump\Http\HttpDumper;
use Sakoo\Framework\Core\VarDump\Http\HttpFormatter;

final class HttpVarDumpTest extends TestCase
{
	#[Test]
	public function http_formatter_format_is_a_no_op(): void
	{
		ob_start();
		(new HttpFormatter())->format('hello');
		$output = ob_get_clean();

		$this->assertSame('', $output);
	}

	#[Test]
	public function http_formatter_accepts_any_type(): void
	{
		ob_start();
		(new HttpFormatter())->format(null);
		(new HttpFormatter())->format(42);
		(new HttpFormatter())->format(['a', 'b']);
		(new HttpFormatter())->format(new \stdClass());
		(new HttpFormatter())->format(true);
		ob_end_clean();

		$this->assertTrue(true);
	}

	#[DataProvider('dumpValues')]
	#[Test]
	public function http_dumper_calls_format_with_correct_value(mixed $value): void
	{
		$mockFormatter = $this->createMock(Formatter::class);
		$mockFormatter->expects($this->once())
			->method('format')
			->with($this->identicalTo($value));

		(new HttpDumper($mockFormatter))->dump($value);
	}

	public static function dumpValues(): \Generator
	{
		yield 'string' => ['hello'];
		yield 'integer' => [42];
		yield 'float' => [3.14];
		yield 'boolean' => [true];
		yield 'null' => [null];
	}
}
