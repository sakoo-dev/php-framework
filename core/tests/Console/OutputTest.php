<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Console;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sakoo\Framework\Core\Console\Output;

class OutputTest extends TestCase
{
	private function captureOutput(callable $callback): string
	{
		ob_start();
		$callback();

		return ob_get_clean();
	}

	#[Test]
	public function text_output_without_formatting(): void
	{
		$output = new Output(false);

		$capturedOutput = $this->captureOutput(fn () => $output->text('Hello World'));

		$this->assertEquals('Hello World', $capturedOutput);
		$this->assertEquals(['Hello World'], $output->getBuffer());
	}

	#[Test]
	public function block_output_adds_newline(): void
	{
		$output = new Output(false);
		$capturedOutput = $this->captureOutput(fn () => $output->block('Hello World'));

		$this->assertEquals('Hello World' . PHP_EOL, $capturedOutput);
	}

	#[Test]
	public function format_text_with_colors(): void
	{
		$output = new Output(true);
		$capturedOutput = $this->captureOutput(fn () => $output->text('Colored Text', Output::COLOR_RED));

		$this->assertEquals("\033[31mColored Text\033[0m", $capturedOutput);
	}

	#[Test]
	public function format_text_with_multiple_styles(): void
	{
		$output = new Output(true);
		$capturedOutput = $this->captureOutput(fn () => $output->text('Styled Text', Output::COLOR_GREEN, Output::BG_BLACK, Output::STYLE_BOLD));

		$this->assertEquals("\033[1;32;40mStyled Text\033[0m", $capturedOutput);
	}

	#[Test]
	public function silent_mode(): void
	{
		$output = new Output(false);
		$output->setSilentMode();

		$capturedOutput = $this->captureOutput(fn () => $output->text('This should not be output'));

		$this->assertEquals('', $capturedOutput);
	}

	#[Test]
	public function array_messages(): void
	{
		$output = new Output(false);
		$capturedOutput = $this->captureOutput(fn () => $output->block(['Line 1', 'Line 2']));

		$this->assertEquals('Line 1' . PHP_EOL . 'Line 2' . PHP_EOL, $capturedOutput);
	}

	#[Test]
	public function shortcut_methods(): void
	{
		$output = new Output(true);

		$successOutput = $this->captureOutput(fn () => $output->success('Success message'));
		$this->assertEquals("\033[1;32mSuccess message\033[0m" . PHP_EOL, $successOutput);

		$infoOutput = $this->captureOutput(fn () => $output->info('Info message'));
		$this->assertEquals("\033[1;34mInfo message\033[0m" . PHP_EOL, $infoOutput);

		$warningOutput = $this->captureOutput(fn () => $output->warning('Warning message'));
		$this->assertEquals("\033[1;33mWarning message\033[0m" . PHP_EOL, $warningOutput);

		$errorOutput = $this->captureOutput(fn () => $output->error('Error message'));
		$this->assertEquals("\033[1;31mError message\033[0m" . PHP_EOL, $errorOutput);
	}

	#[Test]
	public function get_display(): void
	{
		$output = new Output(false);
		$output->setSilentMode();
		$output->text('Line 1');
		$output->text('Line 2');

		$this->assertEquals('Line 1Line 2', $output->getDisplay());
	}

	#[Test]
	public function new_line(): void
	{
		$output = new Output(false);
		$capturedOutput = $this->captureOutput(fn () => $output->newLine());

		$this->assertEquals(PHP_EOL . PHP_EOL, $capturedOutput);
	}

	#[Test]
	public function supports_colors_returns_bool(): void
	{
		$this->assertIsBool((new Output(false))->supportsColors());
		$this->assertIsBool((new Output(true))->supportsColors());
	}

	#[Test]
	public function supports_colors_returns_true_when_forced_on(): void
	{
		$this->assertTrue((new Output(true))->supportsColors());
	}

	#[Test]
	public function format_text_with_color_support_enabled_but_no_format_params_returns_plain(): void
	{
		$this->assertSame('plain text', (new Output(true))->formatText('plain text'));
	}

	#[Test]
	public function format_text_applies_ansi_codes_when_colors_enabled(): void
	{
		$result = (new Output(true))->formatText('colored', Output::COLOR_GREEN);

		$this->assertStringContainsString("\033[", $result);
		$this->assertStringContainsString('colored', $result);
	}

	#[Test]
	public function silent_mode_can_be_disabled(): void
	{
		$output = new Output(false);
		$output->setSilentMode(true);

		$this->assertSame('', $this->captureOutput(fn () => $output->text('hidden')));

		$output->setSilentMode(false);

		$this->assertSame('visible', $this->captureOutput(fn () => $output->text('visible')));
	}
}
