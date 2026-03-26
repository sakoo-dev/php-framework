<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Attributes;

/**
 * Marker attribute that excludes the annotated class, method, property, or
 * constant from the framework's automatic documentation generator.
 *
 * Apply this attribute to internal implementation details — such as magic methods,
 * framework-internal helpers, and infrastructure glue — that should not appear in
 * the public API documentation produced by the doc:gen console command.
 *
 * The attribute targets all declaration types (TARGET_ALL) so it can be placed on
 * classes, interfaces, traits, enums, methods, properties, constants, and
 * function parameters without restriction.
 */
#[\Attribute(\Attribute::TARGET_ALL)]
final class DontDocument
{
	public function __construct() {}
}
