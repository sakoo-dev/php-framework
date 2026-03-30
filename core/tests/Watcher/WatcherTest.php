<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Watcher;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Locker\Locker;
use Sakoo\Framework\Core\Tests\TestCase;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;
use Sakoo\Framework\Core\Watcher\EventTypes;
use Sakoo\Framework\Core\Watcher\Inotify\Event;
use Sakoo\Framework\Core\Watcher\Inotify\File;
use Sakoo\Framework\Core\Watcher\Inotify\Inotify;
use Sakoo\Framework\Core\Watcher\Watcher;

final class WatcherTest extends TestCase
{
	#[DataProvider('masks')]
	#[Test]
	public function watcher_works_properly(int $mask, string $callbackFn): void
	{
		$inotify = $this->createMock(Inotify::class);
		$fileSystemAction = $this->createMock(FileSystemAction::class);

		$file = new File(100, '/tmp/test', $fileSystemAction, new Locker());
		$event = new Event($file, ['mask' => $mask, 'wd' => 100, 'cookie' => 0]);

		$inotify->method('wait')->willReturn(set([$event]));

		$fileSystemAction
			->expects($this->once())
			->method($callbackFn)
			->with($this->isInstanceOf(Event::class));

		(new Watcher($inotify))->check();
	}

	public static function masks(): \Generator
	{
		yield [IN_MODIFY, 'fileModified'];
		yield [IN_MOVE_SELF, 'fileMoved'];
		yield [IN_DELETE_SELF, 'fileDeleted'];
	}

	#[Test]
	public function inotify_file_get_id(): void
	{
		$file = new File(42, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());

		$this->assertSame(42, $file->getId());
	}

	#[Test]
	public function inotify_file_get_path(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());

		$this->assertSame('/tmp/watch.php', $file->getPath());
	}

	#[Test]
	public function inotify_file_get_callback(): void
	{
		$action = $this->createMock(FileSystemAction::class);
		$file = new File(1, '/tmp/watch.php', $action, new Locker());

		$this->assertSame($action, $file->getCallback());
	}

	#[Test]
	public function inotify_file_get_locker(): void
	{
		$locker = new Locker();
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), $locker);

		$this->assertSame($locker, $file->getLocker());
	}

	#[Test]
	public function inotify_event_get_file(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_MODIFY, 'wd' => 1, 'cookie' => 0]);

		$this->assertSame($file, $event->getFile());
	}

	#[Test]
	public function inotify_event_get_handler_id(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_MODIFY, 'wd' => 77, 'cookie' => 0]);

		$this->assertSame(77, $event->getHandlerId());
	}

	#[Test]
	public function inotify_event_get_name_returns_file_path(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_MODIFY, 'wd' => 1, 'cookie' => 0]);

		$this->assertSame('/tmp/watch.php', $event->getName());
	}

	#[Test]
	public function inotify_event_get_group_id(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_MODIFY, 'wd' => 1, 'cookie' => 99]);

		$this->assertSame(99, $event->getGroupId());
	}

	#[Test]
	public function inotify_event_get_type_modify(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_MODIFY, 'wd' => 1, 'cookie' => 0]);

		$this->assertSame(EventTypes::MODIFY, $event->getType());
	}

	#[Test]
	public function inotify_event_get_type_move(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_MOVE_SELF, 'wd' => 1, 'cookie' => 0]);

		$this->assertSame(EventTypes::MOVE, $event->getType());
	}

	#[Test]
	public function inotify_event_get_type_delete(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => IN_DELETE_SELF, 'wd' => 1, 'cookie' => 0]);

		$this->assertSame(EventTypes::DELETE, $event->getType());
	}

	#[Test]
	public function inotify_event_get_type_defaults_to_modify_for_unknown_mask(): void
	{
		$file = new File(1, '/tmp/watch.php', $this->createMock(FileSystemAction::class), new Locker());
		$event = new Event($file, ['mask' => 0, 'wd' => 1, 'cookie' => 0]);

		$this->assertSame(EventTypes::MODIFY, $event->getType());
	}
}
