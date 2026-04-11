<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Tests\TestCase;

final class StreamTest extends TestCase
{
	#[Test]
	public function it_creates_stream_from_string(): void
	{
		$stream = Stream::createFromString('Hello, World!');

		$this->assertSame('Hello, World!', (string) $stream);
	}

	#[Test]
	public function it_creates_empty_stream(): void
	{
		$stream = Stream::createFromString();

		$this->assertSame('', (string) $stream);
	}

	#[Test]
	public function it_creates_stream_from_resource(): void
	{
		$resource = fopen('php://temp', 'r+');
		fwrite($resource, 'test');
		rewind($resource);

		$stream = Stream::create($resource);

		$this->assertSame('test', (string) $stream);
	}

	#[Test]
	public function it_returns_size(): void
	{
		$stream = Stream::createFromString('12345');

		$this->assertSame(5, $stream->getSize());
	}

	#[Test]
	public function it_returns_null_size_when_detached(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->assertNull($stream->getSize());
	}

	#[Test]
	public function it_tells_current_position(): void
	{
		$stream = Stream::createFromString('abcdef');
		$stream->read(3);

		$this->assertSame(3, $stream->tell());
	}

	#[Test]
	public function it_detects_eof(): void
	{
		$stream = Stream::createFromString('ab');
		$stream->read(2);
		$stream->read(1);

		$this->assertTrue($stream->eof());
	}

	#[Test]
	public function it_is_not_eof_before_end(): void
	{
		$stream = Stream::createFromString('ab');
		$stream->read(1);

		$this->assertFalse($stream->eof());
	}

	#[Test]
	public function it_is_seekable(): void
	{
		$stream = Stream::createFromString('test');

		$this->assertTrue($stream->isSeekable());
	}

	#[Test]
	public function it_seeks_to_position(): void
	{
		$stream = Stream::createFromString('abcdef');
		$stream->seek(3);

		$this->assertSame('def', $stream->getContents());
	}

	#[Test]
	public function it_rewinds(): void
	{
		$stream = Stream::createFromString('hello');
		$stream->read(3);
		$stream->rewind();

		$this->assertSame(0, $stream->tell());
	}

	#[Test]
	public function it_is_writable(): void
	{
		$stream = Stream::createFromString();

		$this->assertTrue($stream->isWritable());
	}

	#[Test]
	public function it_writes_data(): void
	{
		$stream = Stream::createFromString();
		$bytes = $stream->write('hello');
		$stream->rewind();

		$this->assertSame(5, $bytes);
		$this->assertSame('hello', $stream->getContents());
	}

	#[Test]
	public function it_is_readable(): void
	{
		$stream = Stream::createFromString('test');

		$this->assertTrue($stream->isReadable());
	}

	#[Test]
	public function it_reads_data(): void
	{
		$stream = Stream::createFromString('hello world');

		$this->assertSame('hello', $stream->read(5));
	}

	#[Test]
	public function it_gets_contents(): void
	{
		$stream = Stream::createFromString('full content');
		$stream->seek(5);

		$this->assertSame('content', $stream->getContents());
	}

	#[Test]
	public function it_returns_metadata(): void
	{
		$stream = Stream::createFromString('test');
		$meta = $stream->getMetadata();

		$this->assertIsArray($meta);
		$this->assertArrayHasKey('mode', $meta);
	}

	#[Test]
	public function it_returns_single_metadata_key(): void
	{
		$stream = Stream::createFromString('test');

		$this->assertNotNull($stream->getMetadata('mode'));
		$this->assertNull($stream->getMetadata('nonexistent'));
	}

	#[Test]
	public function it_closes_stream(): void
	{
		$stream = Stream::createFromString('test');
		$stream->close();

		$this->assertSame('', (string) $stream);
	}

	#[Test]
	public function it_detaches_resource(): void
	{
		$stream = Stream::createFromString('test');
		$resource = $stream->detach();

		$this->assertIsResource($resource);
		$this->assertNull($stream->detach());
	}

	#[Test]
	public function it_throws_on_tell_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->expectException(\RuntimeException::class);
		$stream->tell();
	}

	#[Test]
	public function it_throws_on_seek_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->expectException(\RuntimeException::class);
		$stream->seek(0);
	}

	#[Test]
	public function it_throws_on_write_after_detach(): void
	{
		$stream = Stream::createFromString();
		$stream->detach();

		$this->expectException(\RuntimeException::class);
		$stream->write('data');
	}

	#[Test]
	public function it_throws_on_read_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->expectException(\RuntimeException::class);
		$stream->read(1);
	}

	#[Test]
	public function it_throws_on_get_contents_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->expectException(\RuntimeException::class);
		$stream->getContents();
	}

	#[Test]
	public function it_is_not_seekable_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->assertFalse($stream->isSeekable());
	}

	#[Test]
	public function it_is_not_writable_after_detach(): void
	{
		$stream = Stream::createFromString();
		$stream->detach();

		$this->assertFalse($stream->isWritable());
	}

	#[Test]
	public function it_is_not_readable_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->assertFalse($stream->isReadable());
	}

	#[Test]
	public function to_string_returns_empty_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->assertSame('', (string) $stream);
	}

	#[Test]
	public function metadata_returns_empty_array_after_detach(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->assertSame([], $stream->getMetadata());
		$this->assertNull($stream->getMetadata('mode'));
	}

	#[Test]
	public function eof_returns_true_when_detached(): void
	{
		$stream = Stream::createFromString('test');
		$stream->detach();

		$this->assertTrue($stream->eof());
	}
}
