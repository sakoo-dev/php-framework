<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when the container attempts to instantiate a class that cannot be
 * constructed directly — for example an abstract class, an interface, or a class
 * with a private constructor. Implements PSR-11 ContainerExceptionInterface.
 */
class ClassNotInstantiableException extends Exception implements ContainerExceptionInterface {}
