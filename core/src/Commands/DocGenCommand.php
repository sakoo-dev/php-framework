<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Commands;

use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\Doc\Doc;
use Sakoo\Framework\Core\Doc\Formatters\DocFormatter;
use Sakoo\Framework\Core\Doc\Formatters\Formatter;
use Sakoo\Framework\Core\Doc\Formatters\TocFormatter;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Finder\SplFileObject;
use Sakoo\Framework\Core\Path\Path;

/**
 * Console command that generates Markdown API documentation from PHP source files.
 *
 * Produces two output artefacts for wiki-style documentation hosting:
 *
 * 1. Main doc file ($docPath)     — full API reference rendered by DocFormatter,
 *    covering every undocumented public class and method found in the source tree.
 * 2. Sidebar file ($sidebarPath)  — table-of-contents rendered by TocFormatter,
 *    suitable for use as a wiki _Sidebar.md navigation file.
 * 3. Footer file ($footerPath)    — a one-line "Powered by …" attribution string
 *    written directly without a formatter.
 *
 * The source tree to document defaults to the framework core directory but can be
 * overridden by passing a path as the second positional CLI argument.
 *
 * All three output paths are injected via the constructor so they can be configured
 * per-project without subclassing.
 */
class DocGenCommand extends Command
{
	public function __construct(
		private readonly string $docPath,
		private readonly string $sidebarPath,
		private readonly string $footerPath,
	) {}

	/**
	 * Returns the CLI argument name 'doc:gen' used to invoke this command.
	 */
	public static function getName(): string
	{
		return 'doc:gen';
	}

	/**
	 * Returns a single-line description of this command for help listings.
	 */
	public static function getDescription(): string
	{
		return 'Generates Document of Framework';
	}

	/**
	 * Discovers PHP files in the target directory, generates the main documentation
	 * and TOC sidebar via Doc, writes the footer attribution, then reports success.
	 * The optional second positional argument overrides the source directory path.
	 */
	public function run(Input $input, Output $output): int
	{
		$output->block('Generating ...', style: Output::COLOR_CYAN);

		/**
		 * @var array<SplFileObject> $finder
		 *
		 * @phpstan-ignore argument.type
		 */
		$finder = Path::getPHPFilesOf($input->getArgument(1) ?? Path::getCoreDir());

		/** @var Formatter $formatter */
		$formatter = resolve(DocFormatter::class);
		$docFile = File::open(Disk::Local, $this->docPath);
		(new Doc($finder, $formatter, $docFile))->generate();

		/** @var Formatter $tocFormatter */
		$tocFormatter = resolve(TocFormatter::class);
		$wikiSideBar = File::open(Disk::Local, $this->sidebarPath);
		(new Doc($finder, $tocFormatter, $wikiSideBar))->generate();

		$wikiFooter = File::open(Disk::Local, $this->footerPath);
		$wikiFooter->write('Powered by Sakoo Development Group - ' . date('Y'));

		$output->block('Document has been Generated Successfully!', Output::COLOR_GREEN);

		return Output::SUCCESS;
	}
}
