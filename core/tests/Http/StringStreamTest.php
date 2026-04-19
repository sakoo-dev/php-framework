<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\StringStream;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Unit tests for StringStream — the zero-syscall, read-only in-memory stream.
 */
final class StringStreamTest extends TestCase
{
	#[Test]
	public function of_named_constructor_creates_instance(): void
	{
		$stream = StringStream::of('hello');

		$this->assertInstanceOf(StringStream::class, $stream);
		$this->assertSame('hello', (string) $stream);
	}

	#[Test]
	public function empty_content_by_default(): void
	{
		$stream = new StringStream();

		$this->assertSame('', (string) $stream);
		$this->assertSame(0, $stream->getSize());
	}

	#[Test]
	public function get_size_returns_byte_length(): void
	{
		$stream = StringStream::of('hello');

		$this->assertSame(5, $stream->getSize());
	}

	#[Test]
	public function tell_starts_at_zero(): void
	{
		$stream = StringStream::of('hello');

		$this->assertSame(0, $stream->tell());
	}

	#[Test]
	public function tell_advances_after_read(): void
	{
		$stream = StringStream::of('hello');
		$stream->read(3);

		$this->assertSame(3, $stream->tell());
	}

	#[Test]
	public function eof_false_at_start(): void
	{
		$stream = StringStream::of('hi');

		$this->assertFalse($stream->eof());
	}

	#[Test]
	public function eof_true_after_full_read(): void
	{
		$stream = StringStream::of('hi');
		$stream->read(2);

		$this->assertTrue($stream->eof());
	}

	#[Test]
	public function eof_true_on_empty_stream(): void
	{
		$stream = StringStream::of('');

		$this->assertTrue($stream->eof());
	}

	#[Test]
	public function is_seekable(): void
	{
		$this->assertTrue(StringStream::of('test')->isSeekable());
	}

	#[Test]
	public function seek_set_moves_to_absolute_position(): void
	{
		$stream = StringStream::of('abcdef');
		$stream->seek(3, SEEK_SET);

		$this->assertSame(3, $stream->tell());
	}

	#[Test]
	public function seek_cur_moves_relative_to_position(): void
	{
		$stream = StringStream::of('abcdef');
		$stream->seek(2);
		$stream->seek(2, SEEK_CUR);

		$this->assertSame(4, $stream->tell());
	}

	#[Test]
	public function seek_end_moves_relative_to_end(): void
	{
		$stream = StringStream::of('abcdef');
		$stream->seek(0, SEEK_END);

		$this->assertSame(6, $stream->tell());
	}

	#[Test]
	public function seek_end_negative_offset_positions_before_end(): void
	{
		$stream = StringStream::of('abcdef');
		$stream->seek(-2, SEEK_END);

		$this->assertSame(4, $stream->tell());
	}

	#[Test]
	public function seek_clamps_position_to_zero_on_negative_result(): void
	{
		$stream = StringStream::of('abc');
		$stream->seek(-100, SEEK_SET);

		$this->assertSame(0, $stream->tell());
	}

	#[Test]
	public function seek_clamps_position_to_length_on_overflow(): void
	{
		$stream = StringStream::of('abc');
		$stream->seek(999, SEEK_SET);

		$this->assertSame(3, $stream->tell());
	}

	#[Test]
	public function seek_throws_on_invalid_whence(): void
	{
		$stream = StringStream::of('abc');

		$this->expectException(\RuntimeException::class);
		$stream->seek(0, 99);
	}

	#[Test]
	public function rewind_resets_position_to_zero(): void
	{
		$stream = StringStream::of('hello');
		$stream->read(5);
		$stream->rewind();

		$this->assertSame(0, $stream->tell());
		$this->assertSame('hello', (string) $stream);
	}

	#[Test]
	public function is_not_writable(): void
	{
		$this->assertFalse(StringStream::of('test')->isWritable());
	}

	#[Test]
	public function write_throws_runtime_exception(): void
	{
		$stream = StringStream::of('test');

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('StringStream is read-only.');
		$stream->write('extra');
	}

	#[Test]
	public function is_readable(): void
	{
		$this->assertTrue(StringStream::of('test')->isReadable());
	}

	#[Test]
	public function read_returns_chunk_and_advances_position(): void
	{
		$stream = StringStream::of('hello world');

		$this->assertSame('hello', $stream->read(5));
		$this->assertSame(5, $stream->tell());
		$this->assertSame(' worl', $stream->read(5));
	}

	#[Test]
	public function read_returns_less_than_requested_near_end(): void
	{
		$stream = StringStream::of('abc');
		$stream->seek(2);

		$this->assertSame('c', $stream->read(100));
	}

	#[Test]
	public function get_contents_returns_remainder(): void
	{
		$stream = StringStream::of('abcdef');
		$stream->seek(3);

		$this->assertSame('def', $stream->getContents());
		$this->assertSame(6, $stream->tell());
	}

	#[Test]
	public function get_contents_from_start_returns_everything(): void
	{
		$stream = StringStream::of('hello');

		$this->assertSame('hello', $stream->getContents());
	}

	#[Test]
	public function get_metadata_returns_empty_array_without_key(): void
	{
		$stream = StringStream::of('test');

		$this->assertSame([], $stream->getMetadata());
	}

	#[Test]
	public function get_metadata_returns_null_for_any_key(): void
	{
		$stream = StringStream::of('test');

		$this->assertNull($stream->getMetadata('mode'));
		$this->assertNull($stream->getMetadata('wrapper_type'));
	}

	#[Test]
	public function close_is_a_no_op(): void
	{
		$stream = StringStream::of('test');
		$stream->close();

		$this->assertSame('test', (string) $stream);
	}

	#[Test]
	public function detach_returns_null(): void
	{
		$stream = StringStream::of('test');

		$this->assertNull($stream->detach());
		$this->assertSame('test', (string) $stream, 'Content still accessible after detach');
	}

	#[Test]
	public function multiple_reads_consume_stream_sequentially(): void
	{
		$stream = StringStream::of('abcdef');

		$this->assertSame('ab', $stream->read(2));
		$this->assertSame('cd', $stream->read(2));
		$this->assertSame('ef', $stream->read(2));
		$this->assertTrue($stream->eof());
	}

	#[Test]
	public function rewind_then_read_restarts_from_beginning(): void
	{
		$stream = StringStream::of('hello');
		$stream->read(5);
		$stream->rewind();

		$this->assertSame('hello', $stream->read(5));
	}

	#[Test]
	public function to_string_does_not_advance_position(): void
	{
		$stream = StringStream::of('hello');
		$stream->read(3);

		$full = (string) $stream;

		$this->assertSame('hello', $full);
		$this->assertSame(3, $stream->tell(), 'Position unchanged by __toString');
	}
}
