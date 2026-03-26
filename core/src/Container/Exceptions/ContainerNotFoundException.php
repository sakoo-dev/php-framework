<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Exceptions;

use Psr\Container\NotFoundExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when the container is asked to resolve an identifier that has no registered
 * binding or singleton. Implements PSR-11 NotFoundExceptionInterface so callers can
 * distinguish a missing registration from other container errors.
 */
class ContainerNotFoundException extends Exception implements NotFoundExceptionInterface {}
