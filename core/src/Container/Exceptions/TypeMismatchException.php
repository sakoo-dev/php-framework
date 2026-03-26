<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a concrete class or object is registered against an interface it does
 * not implement. Prevents silent runtime errors that would only surface later during
 * actual usage of the resolved object. Implements PSR-11 ContainerExceptionInterface.
 */
class TypeMismatchException extends Exception implements ContainerExceptionInterface {}
