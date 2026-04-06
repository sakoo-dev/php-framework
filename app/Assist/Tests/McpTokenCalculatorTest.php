<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\McpTokenCalculator;
use PHPUnit\Framework\Attributes\Test;
use System\Testing\TestCase;

/**
 * Unit tests for {@see McpTokenCalculator}.
 *
 * Validates token estimation accuracy, empty-input handling, and
 * multi-byte character support for the cl100k_base approximation.
 */
final class McpTokenCalculatorTest extends TestCase
{
	private McpTokenCalculator $calculator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->calculator = new McpTokenCalculator();
	}

	#[Test]
	public function empty_string_returns_zero(): void
	{
		$this->assertSame(0, $this->calculator->countText(''));
	}

	#[Test]
	public function single_word_returns_at_least_one_token(): void
	{
		$this->assertGreaterThanOrEqual(1, $this->calculator->countText('hello'));
	}

	#[Test]
	public function short_sentence_produces_reasonable_estimate(): void
	{
		$text = 'The quick brown fox jumps over the lazy dog.';
		$tokens = $this->calculator->countText($text);

		$this->assertGreaterThan(5, $tokens);
		$this->assertLessThan(20, $tokens);
	}

	#[Test]
	public function code_snippet_produces_reasonable_estimate(): void
	{
		$code = "<?php\nfunction hello(): string {\n    return 'world';\n}\n";
		$tokens = $this->calculator->countText($code);

		$this->assertGreaterThan(5, $tokens);
		$this->assertLessThan(30, $tokens);
	}

	#[Test]
	public function multibyte_text_is_handled(): void
	{
		$text = 'これはテストです。';
		$tokens = $this->calculator->countText($text);

		$this->assertGreaterThanOrEqual(1, $tokens);
	}

	#[Test]
	public function long_text_scales_linearly(): void
	{
		$short = str_repeat('word ', 10);
		$long = str_repeat('word ', 100);

		$shortTokens = $this->calculator->countText($short);
		$longTokens = $this->calculator->countText($long);

		$ratio = $longTokens / max($shortTokens, 1);
		$this->assertGreaterThan(5, $ratio);
		$this->assertLessThan(15, $ratio);
	}

	#[Test]
	public function whitespace_only_returns_nonzero(): void
	{
		$this->assertGreaterThanOrEqual(1, $this->calculator->countText('   '));
	}
}
