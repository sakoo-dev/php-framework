<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\Method;

use Sakoo\Framework\Core\Doc\Object\PHPDoc;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a [at-sign]method PHPDoc tag cannot be parsed into a VirtualMethodObject.
 *
 * Raised by VirtualMethodObject::parse() when the tag line is structurally invalid —
 * for example when parentheses are missing or unbalanced. The Doc generator catches
 * this exception and silently skips the malformed tag so a single bad [at-sign]method
 * annotation does not abort the entire documentation generation run.
 */
class InvalidVirtualMethodDefinitionException extends Exception {}
