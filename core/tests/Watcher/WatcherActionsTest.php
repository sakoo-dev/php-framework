<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Watcher;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Locker\Locker;
use Sakoo\Framework\Core\Tests\TestCase;
use Sakoo\Framework\Core\Watcher\Contracts\FileSystemAction;
use Sakoo\Framework\Core\Watcher\Inotify\Event;
use Sakoo\Framework\Core\Watcher\Inotify\File;
use Sakoo\Framework\Core\Watcher\WatcherActions;

final class WatcherActionsTest extends TestCase
{
	private function makeEvent(bool $locked = false): Event
	{
		$locker = new Locker();

		if ($locked) {
			$locker->lock();
		}

		$file = new File(1, '/tmp/test-file.php', $this->createMock(FileSystemAction::class), $locker);
		$inotifyEvent = ['mask' => IN_MODIFY, 'wd' => 1, 'cookie' => 0];

		return new Event($file, $inotifyEvent);
	}

	#[Test]
	public function file_modified_acquires_lock_when_not_already_locked(): void
	{
		$actions = new class extends WatcherActions {};
		$event = $this->makeEvent(locked: false);

		$this->assertFalse($event->getFile()->getLocker()->isLocked());

		$actions->fileModified($event);

		$this->assertTrue($event->getFile()->getLocker()->isLocked());
	}

	#[Test]
	public function file_modified_does_not_change_lock_when_already_held(): void
	{
		$actions = new class extends WatcherActions {};
		$event = $this->makeEvent(locked: true);

		$actions->fileModified($event);

		$this->assertTrue($event->getFile()->getLocker()->isLocked());
	}
}
