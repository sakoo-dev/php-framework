<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Str;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Str\Str;
use Sakoo\Framework\Core\Tests\TestCase;

final class StrTest extends TestCase
{
	#[Test]
	public function length_function_works_properly(): void
	{
		$string = new Str('');
		$this->assertEquals(0, $string->length());

		$string = new Str('Hello, World!');
		$this->assertEquals(13, $string->length());
	}

	#[Test]
	public function uppercase_words_function_works_properly(): void
	{
		$string = new Str('hello, world!');
		$this->assertEquals('Hello, World!', $string->uppercaseWords());
	}

	#[Test]
	public function uppercase_function_works_properly(): void
	{
		$string = new Str('Hello, World!');
		$this->assertEquals('HELLO, WORLD!', $string->uppercase());
	}

	#[Test]
	public function lowercase_function_works_properly(): void
	{
		$string = new Str('Hello, World!');
		$this->assertEquals('hello, world!', $string->lowercase());
	}

	#[Test]
	public function upper_first_function_works_properly(): void
	{
		$string = new Str('hello world');
		$this->assertEquals('Hello world', $string->upperFirst());
	}

	#[Test]
	public function lower_first_function_works_properly(): void
	{
		$string = new Str('HELLO WORLD');
		$this->assertEquals('hELLO WORLD', $string->lowerFirst());
	}

	#[Test]
	public function reverse_function_works_properly(): void
	{
		$string = new Str('Hello, World!');
		$this->assertEquals('!dlroW ,olleH', $string->reverse());
	}

	#[Test]
	public function contains_function_works_properly(): void
	{
		$string = new Str('Hello, World!');
		$this->assertTrue($string->contains('World'));
		$this->assertFalse($string->contains('foo'));
	}

	#[Test]
	public function replace_function_works_properly(): void
	{
		$string = new Str('Hello, World!');
		$this->assertEquals('Hi, World!', $string->replace('Hello', 'Hi'));
	}

	#[Test]
	public function trim_function_works_properly(): void
	{
		$string = new Str('   Hello, World!   ');
		$this->assertEquals('Hello, World!', $string->trim());
	}

	#[DataProvider('slugTexts')]
	#[Test]
	public function slug_function_works_properly(string $str, string $slug): void
	{
		$string = new Str($str);
		$this->assertEquals($slug, $string->slug());
	}

	#[DataProvider('slugTexts')]
	#[Test]
	public function kebab_function_works_properly(string $str, string $slug): void
	{
		$string = new Str($str);
		$this->assertEquals($slug, $string->kebabCase());
	}

	public static function slugTexts(): \Generator
	{
		yield ['HELLO WORLD', 'hello-world'];
		yield ['hello world', 'hello-world'];
		yield ['Hello World', 'hello-world'];
		yield ['HelloWorld', 'hello-world'];
		yield ['hello_world', 'hello-world'];
		yield ['Something', 'something'];
		yield ['hello-world', 'hello-world'];
		yield ['!@#$%^&*()', ''];
		yield ['I ♥️ Emoji', 'i-emoji'];
		yield ['?eHE_llo-worlD!?', 'e-he-llo-worl-d'];
	}

	#[DataProvider('camelCaseTexts')]
	#[Test]
	public function camel_case_function_works_properly(string $str, string $camelCase): void
	{
		$string = new Str($str);
		$this->assertEquals($camelCase, $string->camelCase());
	}

	public static function camelCaseTexts(): \Generator
	{
		yield ['HELLO WORLD', 'helloWorld'];
		yield ['hello world', 'helloWorld'];
		yield ['Hello World', 'helloWorld'];
		yield ['HelloWorld', 'helloWorld'];
		yield ['hello_world', 'helloWorld'];
		yield ['Something', 'something'];
		yield ['hello-world', 'helloWorld'];
		yield ['!@#$%^&*()', ''];
		yield ['I ♥️ Emoji', 'iEmoji'];
		yield ['?eHE_llo-worlD!?', 'eHeLloWorlD'];
	}

	#[DataProvider('snakeTexts')]
	#[Test]
	public function snake_function_works_properly(string $str, string $slug): void
	{
		$string = new Str($str);
		$this->assertEquals($slug, $string->snakeCase());
	}

	public static function snakeTexts(): \Generator
	{
		yield ['HELLO WORLD', 'hello_world'];
		yield ['hello world', 'hello_world'];
		yield ['Hello World', 'hello_world'];
		yield ['HelloWorld', 'hello_world'];
		yield ['hello_world', 'hello_world'];
		yield ['Something', 'something'];
		yield ['hello-world', 'hello_world'];
		yield ['!@#$%^&*()', ''];
		yield ['I ♥️ Emoji', 'i_emoji'];
		yield ['?eHE_llo-worlD!?', 'e_he_llo_worl_d'];
	}
}
