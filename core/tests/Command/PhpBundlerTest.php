<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\Watcher\PhpBundler;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File as FileSystem;
use Sakoo\Framework\Core\FileSystem\Storage;
use Sakoo\Framework\Core\Locker\Locker;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Tests\TestCase;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;
use Sakoo\Framework\Core\Watcher\Inotify\Event;
use Sakoo\Framework\Core\Watcher\Inotify\File;

final class PhpBundlerTest extends TestCase
{
	private string $tempFilePath;
	private Storage $tempFile;

	protected function setUp(): void
	{
		parent::setUp();

		$this->tempFilePath = Path::getTempTestDir() . '/command/php-bundler.php';
		$this->tempFile = FileSystem::open(Disk::Local, $this->tempFilePath);
		$this->tempFile->create();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		$this->tempFile->remove();
	}

	private function makeEvent(): Event
	{
		$fileSystemAction = $this->createMock(FileSystemAction::class);
		$locker = new Locker();

		$file = new File(1, $this->tempFilePath, $fileSystemAction, $locker);
		$inotifyEvent = ['mask' => IN_MODIFY, 'wd' => 1, 'cookie' => 0];

		return new Event($file, $inotifyEvent);
	}

	#[Test]
	public function file_modified_logs_path_and_release_locker(): void
	{
		$output = new Output(false);
		$output->setSilentMode();

		$bundler = new PhpBundler(new Input([]), $output);
		$event = $this->makeEvent();

		$this->assertFalse($event->getFile()->getLocker()->isLocked());

		$bundler->fileModified($event);

		$this->assertFalse($event->getFile()->getLocker()->isLocked());

		$this->assertStringContainsString($this->tempFilePath, $output->getDisplay());
		$this->assertMatchesRegularExpression('/changed at \d{2}:\d{2}:\d{2}/', $output->getDisplay());
		$this->assertStringContainsString('Watching', $output->getDisplay());
	}
}
