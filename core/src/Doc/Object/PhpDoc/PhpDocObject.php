<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\PhpDoc;

use Sakoo\Framework\Core\Doc\Object\Class\ClassInterface;
use Sakoo\Framework\Core\Doc\Object\Method\MethodInterface;
use Sakoo\Framework\Core\Regex\Regex;

readonly class PhpDocObject
{
	public function __construct(private ClassInterface|MethodInterface $component) {}

	/**
	 * @return PhpDocLineObject[]
	 */
	public function getLines(): array
	{
		$result = [];

		foreach ($this->getCleanLines() as $line) {
			$result[] = new PhpDocLineObject($line);
		}

		return $result;
	}

	/**
	 * @return string[]
	 */
	private function getCleanLines(): array
	{
		$phpDoc = $this->component->getRawDoc();

		if (!$phpDoc) {
			$phpDoc = $this->getFromInterface();
		}

		if (!$phpDoc) {
			return [];
		}

		$match = (new Regex())
			->startsWith('/**')
			->add('([\s\S]+)')
			->endsWith('*/')
			->match($phpDoc);

		$lines = explode("\n", $match ? $match[1] : '');
		$result = [];

		foreach ($lines as $line) {
			$result[] = trim($line, "/* \t\r\n");
		}

		return $result;
	}

	private function getFromInterface(): string
	{
		$phpDoc = '';

		if (!$this->component instanceof ClassInterface) {
			return $phpDoc;
		}

		foreach ($this->component->getInterfaces() as $interface) {
			if ($interface->hasMethod($this->component->getName())) {
				$phpDoc = $interface->getMethod($this->component->getName())->getDocComment() ?: '';
			}
		}

		return $phpDoc;
	}
}
