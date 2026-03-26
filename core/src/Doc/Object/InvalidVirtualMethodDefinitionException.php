<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a @method PHPDoc tag cannot be parsed into a VirtualMethodObject.
 *
 * Raised by VirtualMethodObject::parse() when the tag line is structurally invalid —
 * for example when parentheses are missing or unbalanced. The Doc generator catches
 * this exception and silently skips the malformed tag so a single bad @method
 * annotation does not abort the entire documentation generation run.
 */
class InvalidVirtualMethodDefinitionException extends Exception {}
