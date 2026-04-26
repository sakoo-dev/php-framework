<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\Diff\Exception\MalformedDiffException;
use App\Assist\AI\Mcp\Diff\PatchApplier;
use PHPUnit\Framework\Attributes\Test;
use System\Testing\TestCase;

final class PatchApplierTest extends TestCase
{
	private string $tmpFile;

	protected function setUp(): void
	{
		parent::setUp();
		$this->tmpFile = tempnam(sys_get_temp_dir(), 'sakoo-patch-');
	}

	protected function tearDown(): void
	{
		if (is_file($this->tmpFile)) {
			unlink($this->tmpFile);
		}
		parent::tearDown();
	}

	#[Test]
	public function it_preserves_trailing_newline_after_patch(): void
	{
		$original = "alpha\nbeta\ngamma\n";
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -2,1 +2,1 @@
				-beta
				+BETA
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$result = (string) file_get_contents($this->tmpFile);
		$this->assertSame("alpha\nBETA\ngamma\n", $result);
	}

	#[Test]
	public function it_does_not_add_trailing_newline_when_original_had_none(): void
	{
		$original = "alpha\nbeta\ngamma"; // no final \n
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -2,1 +2,1 @@
				-beta
				+BETA
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\nBETA\ngamma", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_preserves_crlf_line_endings(): void
	{
		$original = "alpha\r\nbeta\r\ngamma\r\n";
		file_put_contents($this->tmpFile, $original);

		$diff = "@@ -2,1 +2,1 @@\n-beta\n+BETA\n";

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\r\nBETA\r\ngamma\r\n", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_applies_patch_with_leading_and_trailing_context(): void
	{
		$original = "one\ntwo\nthree\nfour\nfive\n";
		file_put_contents($this->tmpFile, $original);

		// Hunk header is line 1, but the actual change is at line 3.
		$diff = <<<'DIFF'
				@@ -1,5 +1,5 @@
				 one
				 two
				-three
				+THREE
				 four
				 five
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("one\ntwo\nTHREE\nfour\nfive\n", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_applies_multiple_hunks_correctly(): void
	{
		$original = "a\nb\nc\nd\ne\nf\ng\nh\n";
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -2,1 +2,1 @@
				-b
				+B
				@@ -7,1 +7,1 @@
				-g
				+G
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("a\nB\nc\nd\ne\nf\nG\nh\n", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_handles_pure_addition(): void
	{
		$original = "alpha\ngamma\n";
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -1,2 +1,3 @@
				 alpha
				+beta
				 gamma
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\nbeta\ngamma\n", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_handles_pure_deletion(): void
	{
		$original = "alpha\nbeta\ngamma\n";
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -1,3 +1,2 @@
				 alpha
				-beta
				 gamma
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\ngamma\n", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_rejects_diff_when_context_does_not_match(): void
	{
		$original = "alpha\nbeta\ngamma\n";
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -1,3 +1,3 @@
				 alpha
				-WRONG
				+REPLACED
				 gamma
				DIFF;

		$this->expectException(MalformedDiffException::class);
		$this->expectExceptionMessageMatches('/does not match file at line 2/');

		try {
			(new PatchApplier())->apply($this->tmpFile, $diff);
		} finally {
			// File must be untouched on validation failure.
			$this->assertSame("alpha\nbeta\ngamma\n", file_get_contents($this->tmpFile));
		}
	}

	#[Test]
	public function it_rejects_diff_with_no_hunks(): void
	{
		file_put_contents($this->tmpFile, "alpha\n");

		$this->expectException(MalformedDiffException::class);

		(new PatchApplier())->apply($this->tmpFile, "--- a/file\n+++ b/file\n");
	}

	#[Test]
	public function it_rejects_diff_with_unparseable_header(): void
	{
		file_put_contents($this->tmpFile, "alpha\n");

		$this->expectException(MalformedDiffException::class);

		(new PatchApplier())->apply($this->tmpFile, "@@ broken @@\n+x\n");
	}

	#[Test]
	public function it_rejects_diff_with_empty_body_line(): void
	{
		file_put_contents($this->tmpFile, "alpha\nbeta\n");

		// Real "empty line" — no leading space, no sigil. This is what an LLM
		// produces when it forgets that blank context lines need a literal " ".
		$diff = "@@ -1,2 +1,2 @@\n alpha\n\n-beta\n+BETA\n";

		$this->expectException(MalformedDiffException::class);

		(new PatchApplier())->apply($this->tmpFile, $diff);
	}

	#[Test]
	public function it_handles_blank_context_line_with_leading_space(): void
	{
		// Blank line in source, properly represented in the diff as " " (space + nothing).
		$original = "alpha\n\ngamma\n";
		file_put_contents($this->tmpFile, $original);

		// The middle hunk line is exactly one space, representing a blank line of context.
		$diff = "@@ -1,3 +1,3 @@\n alpha\n \n-gamma\n+GAMMA\n";

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\n\nGAMMA\n", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_ignores_no_newline_marker(): void
	{
		$original = "alpha\nbeta"; // no trailing newline
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				@@ -2,1 +2,1 @@
				-beta
				\ No newline at end of file
				+BETA
				\ No newline at end of file
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\nBETA", file_get_contents($this->tmpFile));
	}

	#[Test]
	public function it_skips_file_headers_before_first_hunk(): void
	{
		$original = "alpha\nbeta\n";
		file_put_contents($this->tmpFile, $original);

		$diff = <<<'DIFF'
				--- a/file
				+++ b/file
				@@ -2,1 +2,1 @@
				-beta
				+BETA
				DIFF;

		(new PatchApplier())->apply($this->tmpFile, $diff);

		$this->assertSame("alpha\nBETA\n", file_get_contents($this->tmpFile));
	}
}
