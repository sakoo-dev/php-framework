<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\UploadedFile;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Tests\TestCase;

final class UploadedFileTest extends TestCase
{
	#[Test]
	public function it_returns_stream(): void
	{
		$stream = Stream::createFromString('file content');
		$file = new UploadedFile($stream, 12, UPLOAD_ERR_OK, 'test.txt', 'text/plain');

		$this->assertSame('file content', (string) $file->getStream());
	}

	#[Test]
	public function it_returns_size(): void
	{
		$file = new UploadedFile(Stream::createFromString('abc'), 3);

		$this->assertSame(3, $file->getSize());
	}

	#[Test]
	public function it_returns_error(): void
	{
		$file = new UploadedFile(Stream::createFromString(), error: UPLOAD_ERR_INI_SIZE);

		$this->assertSame(UPLOAD_ERR_INI_SIZE, $file->getError());
	}

	#[Test]
	public function it_returns_client_filename(): void
	{
		$file = new UploadedFile(Stream::createFromString(), clientFilename: 'photo.jpg');

		$this->assertSame('photo.jpg', $file->getClientFilename());
	}

	#[Test]
	public function it_returns_client_media_type(): void
	{
		$file = new UploadedFile(Stream::createFromString(), clientMediaType: 'image/jpeg');

		$this->assertSame('image/jpeg', $file->getClientMediaType());
	}

	#[Test]
	public function it_moves_file_to_target(): void
	{
		$stream = Stream::createFromString('moved content');
		$file = new UploadedFile($stream);

		$target = Path::getTempTestDir() . '/uploaded_test_file.txt';
		@mkdir(dirname($target), 0777, true);

		$file->moveTo($target);

		$this->assertFileExists($target);
		$this->assertSame('moved content', file_get_contents($target));

		unlink($target);
	}

	#[Test]
	public function it_throws_on_second_move(): void
	{
		$file = new UploadedFile(Stream::createFromString('data'));

		$target = Path::getTempTestDir() . '/uploaded_double_move.txt';
		@mkdir(dirname($target), 0777, true);

		$file->moveTo($target);

		$this->expectException(\RuntimeException::class);
		$file->moveTo($target);

		@unlink($target);
	}

	#[Test]
	public function it_throws_on_get_stream_after_move(): void
	{
		$file = new UploadedFile(Stream::createFromString('data'));

		$target = Path::getTempTestDir() . '/uploaded_stream_after_move.txt';
		@mkdir(dirname($target), 0777, true);

		$file->moveTo($target);

		$this->expectException(\RuntimeException::class);
		$file->getStream();

		@unlink($target);
	}

	#[Test]
	public function it_throws_on_empty_target_path(): void
	{
		$file = new UploadedFile(Stream::createFromString('data'));

		$this->expectException(\InvalidArgumentException::class);
		$file->moveTo('');
	}

	#[Test]
	public function it_throws_on_stream_when_upload_error(): void
	{
		$file = new UploadedFile(Stream::createFromString(), error: UPLOAD_ERR_NO_FILE);

		$this->expectException(\RuntimeException::class);
		$file->getStream();
	}

	#[Test]
	public function it_throws_on_move_when_upload_error(): void
	{
		$file = new UploadedFile(Stream::createFromString(), error: UPLOAD_ERR_PARTIAL);

		$this->expectException(\RuntimeException::class);
		$file->moveTo('/tmp/wont-happen');
	}
}
