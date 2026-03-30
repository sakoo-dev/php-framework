<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\VarDump;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Tests\TestCase;
use Sakoo\Framework\Core\VarDump\Cli\CliDumper;
use Sakoo\Framework\Core\VarDump\Cli\CliFormatter;
use Sakoo\Framework\Core\VarDump\Dumper;
use Sakoo\Framework\Core\VarDump\Formatter;
use Sakoo\Framework\Core\VarDump\VarDump;

final class VarDumpTest extends TestCase
{
	private function bootDumper(): Output
	{
		$mockOutput = $this->createMock(Output::class);
		$mockOutput->method('formatText')->willReturnCallback(fn ($text) => $text);

		$formatter = new CliFormatter($mockOutput);
		$dumper = new CliDumper($formatter);

		container()->clear();
		container()->singleton(Output::class, $mockOutput);
		container()->singleton(Formatter::class, $formatter);
		container()->singleton(Dumper::class, $dumper);

		return $mockOutput;
	}

	#[DataProvider('dataOutputs')]
	#[Test]
	public function cli_formatter_can_format(mixed $input, string $expectedOutput): void
	{
		$display = '';
		$mockOutput = $this->bootDumper();
		$mockOutput->method('write')->willReturnCallback(function (string $text) use (&$display): void {
			$display .= $text;
		});

		VarDump::dump($input);

		$this->assertStringContainsString($expectedOutput, $display);
	}

	public static function dataOutputs(): \Generator
	{
		yield 'string (plain)' => ['Hello', '"Hello"'];
		yield 'string (with spaces)' => ['Hi there!', '"Hi there!"'];
		yield 'integer (positive)' => [1, '1'];
		yield 'integer (zero)' => [0, '0'];
		yield 'integer (negative)' => [-42, '-42'];
		yield 'float (positive)' => [123.456, '123.456'];
		yield 'float (negative)' => [-7.89, '-7.89'];
		yield 'boolean true' => [true, 'true'];
		yield 'boolean false' => [false, 'false'];
		yield 'null' => [null, 'null'];
		yield 'simple array' => [['foo' => 'dev'], 'Array(1) ['];
		yield 'indexed array' => [[1, 2, 3], 'Array(3) ['];
		yield 'nested array' => [['key' => ['inner' => 'value']], 'Array(1) ['];
		yield 'simple object' => [new class {
			public string $hello = 'World';
		}, 'object(class@anonymous'];
		yield 'object with mixed types' => [new class {
			public int $a = 1;
			private string $b = 'test';
			protected array $c = ['x' => 1];
		}, 'object(class@anonymous'];
	}

	#[Test]
	public function var_dump_static_dump_outputs_multiple_values(): void
	{
		$display = '';
		$mockOutput = $this->bootDumper();
		$mockOutput->method('write')->willReturnCallback(function (string $text) use (&$display): void {
			$display .= $text;
		});

		VarDump::dump('first', 'second');

		$this->assertStringContainsString('"first"', $display);
		$this->assertStringContainsString('"second"', $display);
	}

	#[Test]
	public function var_dump_static_dump_with_no_args_runs_without_error(): void
	{
		$display = '';
		$mockOutput = $this->bootDumper();
		$mockOutput->method('write')->willReturnCallback(function (string $text) use (&$display): void {
			$display .= $text;
		});

		VarDump::dump();

		$this->assertSame('', $display);
	}
}
