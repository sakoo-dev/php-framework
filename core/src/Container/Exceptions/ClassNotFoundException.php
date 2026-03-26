<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when the container cannot locate the requested class during resolution or
 * direct instantiation, typically because the class does not exist or is not
 * autoloadable. Implements PSR-11 ContainerExceptionInterface.
 */
class ClassNotFoundException extends Exception implements ContainerExceptionInterface {}
