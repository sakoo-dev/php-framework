<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Formatters;

use Sakoo\Framework\Core\Doc\Object\NamespaceObject;
use Sakoo\Framework\Core\Markup\Markup;

/**
 * Abstract base for documentation formatters.
 *
 * Provides access to a Markup writer instance and declares the single format()
 * method that all concrete formatters must implement. The Markup instance handles
 * the low-level text/HTML/Markdown construction so formatters can focus entirely
 * on traversal logic and document structure.
 *
 * Two concrete implementations are provided by the framework:
 * - DocFormatter — full API reference with method contracts, usage examples, and PHPDocs.
 * - TocFormatter — compact navigation sidebar with one bullet per namespace.
 */
abstract class Formatter
{
	public function __construct(protected Markup $markup) {}

	/**
	 * Transforms an ordered list of NamespaceObject bags into a formatted string
	 * suitable for writing to a documentation file.
	 *
	 * @param NamespaceObject[] $namespaces
	 */
	abstract public function format(array $namespaces): string;
}
