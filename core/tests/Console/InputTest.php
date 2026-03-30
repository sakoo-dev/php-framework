<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Console;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Tests\TestCase;

final class InputTest extends TestCase
{
	#[Test]
	public function args_parse_correctly(): void
	{
		$_SERVER['argv'] = ['command', 'arg', '--option'];
		$input = new Input();

		$this->assertEquals(['arg'], $input->getArguments());
		$this->assertEquals('arg', $input->getArgument(0));
		$this->assertEquals('true', $input->getOption('option'));
		$this->assertArrayHasKey('option', $input->getOptions());
		$this->assertTrue($input->hasOption('option'));
	}

	#[Test]
	public function long_option_with_value_is_parsed(): void
	{
		$input = new Input(['--env=production']);

		$this->assertSame('production', $input->getOption('env'));
		$this->assertTrue($input->hasOption('env'));
	}

	#[Test]
	public function long_option_without_value_stores_true_string(): void
	{
		$this->assertSame('true', (new Input(['--verbose']))->getOption('verbose'));
	}

	#[Test]
	public function missing_option_returns_null(): void
	{
		$this->assertNull((new Input([]))->getOption('nonexistent'));
		$this->assertFalse((new Input([]))->hasOption('nonexistent'));
	}

	#[Test]
	public function missing_argument_returns_null(): void
	{
		$this->assertNull((new Input([]))->getArgument(99));
	}

	#[Test]
	public function multiple_positional_arguments_are_indexed(): void
	{
		$input = new Input(['foo', 'bar', 'baz']);

		$this->assertSame('foo', $input->getArgument(0));
		$this->assertSame('bar', $input->getArgument(1));
		$this->assertSame('baz', $input->getArgument(2));
	}

	#[Test]
	public function mixed_args_parse_all_types(): void
	{
		$input = new Input(['command', '-v', '--format=json', 'file.txt']);

		$this->assertSame('command', $input->getArgument(0));
		$this->assertSame('file.txt', $input->getArgument(1));
		$this->assertSame('true', $input->getOption('v'));
		$this->assertSame('json', $input->getOption('format'));
	}
}
