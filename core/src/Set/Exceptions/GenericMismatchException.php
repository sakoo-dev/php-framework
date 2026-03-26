<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Set\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a value whose type does not match the Set's inferred generic type is
 * added to or retrieved with a default from a Set instance.
 *
 * Set<T> infers T from the first element inserted. All subsequent elements must
 * share the same PHP gettype() result. This exception signals a violation of that
 * contract, preventing silent type coercion inside collections.
 */
class GenericMismatchException extends Exception {}
