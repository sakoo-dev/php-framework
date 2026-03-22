<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Command;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Commands\DocGenCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\FileSystem\Storage;
use Sakoo\Framework\Core\Path\Path;

final class DocGenCommandTest extends AbstractCommandBase
{
	private Command $command;

	private Storage $docFile;
	private Storage $sidebarFile;
	private Storage $footerFile;

	protected function setUp(): void
	{
		parent::setUp();

		$dir = Path::getTempTestDir() . '/docs';

		$this->docFile = File::open(Disk::Local, "$dir/Doc.md");
		$this->sidebarFile = File::open(Disk::Local, "$dir/_Sidebar.md");
		$this->footerFile = File::open(Disk::Local, "$dir/_Footer.md");

		$this->docFile->remove();
		$this->sidebarFile->remove();
		$this->footerFile->remove();

		$this->command = new DocGenCommand(
			$this->docFile->getPath(),
			$this->sidebarFile->getPath(),
			$this->footerFile->getPath(),
		);
	}

	protected function getCommand(): Command
	{
		return $this->command;
	}

	#[Test]
	public function it_creates_doc_files_properly(): void
	{
		$input = new Input(['doc:gen']);
		$output = new Output();
		$output->setSilentMode();

		$console = new Application($input, $output);
		$console->addCommand($this->command);

		$status = $console->run();
		$result = $output->getDisplay();

		$this->assertEquals(Output::SUCCESS, $status);
		$this->assertStringContainsString('Document has been Generated Successfully!', $result);
		$this->assertTrue($this->docFile->exists());
		$this->assertTrue($this->sidebarFile->exists());
		$this->assertTrue($this->footerFile->exists());
	}
}
