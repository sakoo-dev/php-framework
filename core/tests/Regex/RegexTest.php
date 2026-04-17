<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Regex;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Regex\Regex;
use Sakoo\Framework\Core\Tests\TestCase;

final class RegexTest extends TestCase
{
	#[Test]
	public function regex_start_of_line_works_properly(): void
	{
		$this->assertEquals('^', (new Regex())->startOfLine());
	}

	#[Test]
	public function regex_starts_with_works_properly(): void
	{
		$this->assertEquals('^\/', (new Regex())->startsWith('/'));
		$this->assertEquals('^/', (new Regex())->startsWith(fn (Regex $exp) => $exp->add('/')));
	}

	#[Test]
	public function regex_end_of_line_works_properly(): void
	{
		$this->assertEquals('$', (new Regex())->endOfLine());
	}

	#[Test]
	public function regex_ends_with_works_properly(): void
	{
		$this->assertEquals('\/$', (new Regex())->endsWith('/'));
		$this->assertEquals('/$', (new Regex())->endsWith(fn (Regex $exp) => $exp->add('/')));
	}

	#[Test]
	public function regex_anything_works_properly(): void
	{
		$this->assertEquals('.*', (new Regex())->anything());
	}

	#[Test]
	public function regex_something_works_properly(): void
	{
		$this->assertEquals('.+', (new Regex())->something());
	}

	#[Test]
	public function regex_something_with_works_properly(): void
	{
		$this->assertEquals('[ABC]+', (new Regex())->somethingWith('ABC'));
		$this->assertEquals('[XYZ]+', (new Regex())->somethingWith(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_anything_with_works_properly(): void
	{
		$this->assertEquals('[ABC]*', (new Regex())->anythingWith('ABC'));
		$this->assertEquals('[XYZ]*', (new Regex())->anythingWith(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_something_without_works_properly(): void
	{
		$this->assertEquals('[^ABC]+', (new Regex())->somethingWithout('ABC'));
		$this->assertEquals('[^XYZ]+', (new Regex())->somethingWithout(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_anything_without_works_properly(): void
	{
		$this->assertEquals('[^ABC]*', (new Regex())->anythingWithout('ABC'));
		$this->assertEquals('[^XYZ]*', (new Regex())->anythingWithout(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_wrap_works_properly(): void
	{
		$this->assertEquals('(\.)', (new Regex())->wrap('.'));
		$this->assertEquals('(?:\.)', (new Regex())->wrap('.', true));

		$this->assertEquals('(ABC)', (new Regex())->wrap(fn (Regex $exp) => $exp->add('ABC')));
		$this->assertEquals('(?:ABC)', (new Regex())->wrap(fn (Regex $exp) => $exp->add('ABC'), true));
	}

	#[Test]
	public function regex_bracket_works_properly(): void
	{
		$this->assertEquals('[ABC]', (new Regex())->bracket('ABC'));
		$this->assertEquals('[XYZ]', (new Regex())->bracket(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[DataProvider('specialCharProvider')]
	#[Test]
	public function regex_add_works_properly($char): void
	{
		$this->assertEquals("\\$char", (new Regex())->safeAdd($char));
		$this->assertEquals($char, (new Regex())->add($char));
	}

	public static function specialCharProvider(): \Generator
	{
		yield ['.'];
		yield ['^'];
		yield ['$'];
		yield ['*'];
		yield ['+'];
		yield ['?'];
		yield ['|'];
		yield ['['];
		yield [']'];
		yield ['('];
		yield [']'];
		yield ['\\'];
		yield ['{'];
		yield [']'];
	}

	#[Test]
	public function regex_shorthands_work_properly(): void
	{
		$this->assertEquals('\r\n', (new Regex())->windowsLineBreak());
		$this->assertEquals('\n', (new Regex())->unixLineBreak());
		$this->assertEquals('\t', (new Regex())->tab());
		$this->assertEquals('\s', (new Regex())->space());
		$this->assertEquals('\w', (new Regex())->word());
	}

	#[Test]
	public function regex_maybe_works_properly(): void
	{
		$this->assertEquals('A?', (new Regex())->maybe('A'));
	}

	#[Test]
	public function regex_one_of_works_properly(): void
	{
		$this->assertEquals('(?:A|B|C)', (new Regex())->oneOf(['A', 'B', 'C']));
	}

	#[Test]
	public function regex_digit_works_properly(): void
	{
		$this->assertEquals('\d{3}', (new Regex())->digit(3));
	}

	#[Test]
	public function regex_chars_works_properly(): void
	{
		$this->assertEquals('A-Z', (new Regex())->chars(Regex::ALPHABET_UPPER));
		$this->assertEquals('A-Za-z', (new Regex())->chars(Regex::ALPHABET_UPPER, Regex::ALPHABET_LOWER));
		$this->assertEquals('A-Za-z0-9', (new Regex())->chars(Regex::ALPHABET_UPPER, Regex::ALPHABET_LOWER, Regex::DIGITS));
	}

	#[DataProvider('specialCharProvider')]
	#[Test]
	public function regex_escape_chars_works_properly($char): void
	{
		$this->assertEquals("\\$char", (new Regex())->escapeChars($char));
	}

	#[Test]
	public function regex_lookahead_work_properly(): void
	{
		$this->assertEquals('(?=ABC)', (new Regex())->lookahead('ABC'));
		$this->assertEquals('(?=XYZ)', (new Regex())->lookahead(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_lookbehind_work_properly(): void
	{
		$this->assertEquals('(?<=ABC)', (new Regex())->lookbehind('ABC'));
		$this->assertEquals('(?<=XYZ)', (new Regex())->lookbehind(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_negative_lookahead_work_properly(): void
	{
		$this->assertEquals('(?!ABC)', (new Regex())->negativeLookahead('ABC'));
		$this->assertEquals('(?!XYZ)', (new Regex())->negativeLookahead(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function regex_negative_lookbehind_work_properly(): void
	{
		$this->assertEquals('(?<!ABC)', (new Regex())->negativeLookbehind('ABC'));
		$this->assertEquals('(?<!XYZ)', (new Regex())->negativeLookbehind(fn (Regex $exp) => $exp->add('XYZ')));
	}

	#[Test]
	public function it_can_test_matching_expression(): void
	{
		$regex = (new Regex())
			->startOfLine()
			->startsWith('A')
			->something()
			->endOfLine();

		$this->assertTrue($regex->test('Anything'));
		$this->assertTrue($regex->test('All'));

		$this->assertFalse($regex->test('Everything'));
	}

	#[Test]
	public function it_can_replace_matching_expression(): void
	{
		$regex = (new Regex())
			->startsWith('L')
			->something()
			->endsWith('up');

		$this->assertEquals('Replaced', $regex->replace('Lookup', 'Replaced'));
		$this->assertNotEquals('Replaced', $regex->replace('Pickup', 'Replaced'));
	}

	#[Test]
	public function it_can_split_matching_expression(): void
	{
		$regex = (new Regex())
			->lookahead(fn (Regex $exp) => $exp->add('[' . Regex::ALPHABET_UPPER . ']'))
			->lookbehind(fn (Regex $exp) => $exp->add('[' . Regex::ALPHABET_LOWER . ']'));

		$this->assertEquals(['Hello', 'World'], $regex->split('HelloWorld'));
		$this->assertEquals(['test'], $regex->split('test'));
	}

	#[Test]
	public function it_can_find_matching_expression(): void
	{
		$regex = (new Regex())
			->somethingWith(fn (Regex $exp) => $exp->digit());

		$text = '123 Hello 456 World';

		$this->assertEquals(['123'], $regex->match($text));
		$this->assertEquals(['123', '456'], $regex->matchAll($text)[0]);
	}
}
