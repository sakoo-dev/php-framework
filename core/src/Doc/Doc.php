<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc;

use Sakoo\Framework\Core\Doc\Formatters\Formatter;
use Sakoo\Framework\Core\Doc\Object\ClassObject;
use Sakoo\Framework\Core\Doc\Object\NamespaceObject;
use Sakoo\Framework\Core\FileSystem\Storage;
use Sakoo\Framework\Core\Finder\SplFileObject;

/**
 * Documentation generator that introspects PHP source files and writes formatted output.
 *
 * Accepts a list of SplFileObject instances, a Formatter strategy, and a Storage
 * target file. On generate(), it:
 *
 * 1. Iterates the source files, skipping non-class files and classes marked with
 *    the DontDocument attribute, traits, abstracts, and interfaces.
 * 2. Groups the surviving ClassObject instances by namespace into NamespaceObject bags.
 * 3. Passes the ordered namespace list to the Formatter to produce a Markdown string.
 * 4. Removes any existing target file, recreates it, and writes the rendered string.
 *
 * The Formatter strategy determines the output style — DocFormatter produces a full
 * API reference while TocFormatter produces a navigation sidebar. Both are injected
 * at the call site so Doc itself remains format-agnostic.
 */
readonly class Doc
{
	public function __construct(
		/** @var array<SplFileObject> $files */
		private array $files,
		private Formatter $formatter,
		private Storage $docFile,
	) {}

	/**
	 * Introspects the source files, formats the resulting namespace graph, and
	 * writes the output to the configured Storage target, replacing any prior content.
	 */
	public function generate(): void
	{
		$data = $this->getNamespaceBags($this->files);
		$data = $this->formatter->format($data);
		$this->saveInFile($this->docFile, $data);
	}

	/**
	 * Removes the target file if it exists, creates a fresh one, and writes $data.
	 */
	private function saveInFile(Storage $docFile, string $data): void
	{
		$docFile->remove();
		$docFile->create();
		$docFile->write($data);
	}

	/**
	 * Builds the ordered list of NamespaceObject bags from the given source files.
	 *
	 * Non-class files are skipped. ClassObjects that fail the legality check
	 * (DontDocument attribute, trait, abstract, interface) are also skipped.
	 * The remaining classes are grouped by namespace name, and one NamespaceObject
	 * is created per distinct namespace.
	 *
	 * @param SplFileObject[] $files
	 *
	 * @return NamespaceObject[]
	 */
	private function getNamespaceBags(array $files): array
	{
		$data = [];

		/** @var SplFileObject $file */
		foreach ($files as $file) {
			if (!$file->isClassFile()) {
				continue;
			}

			$reflection = new \ReflectionClass($file->getNamespace());
			$classObject = new ClassObject($reflection);

			if ($classObject->isIllegal()) {
				continue;
			}

			$data[$classObject->getNamespace()][] = $classObject;
		}

		$result = [];

		foreach ($data as $namespace => $classes) {
			$result[] = new NamespaceObject($namespace, $classes);
		}

		return $result;
	}
}
