<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Exceptions;

use Psr\Container\ContainerExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown during container cache operations when a precondition is not satisfied —
 * for example when loadCache() is called but no cache file exists, or when
 * dumpCache() is called but no cache path was configured. Implements PSR-11
 * ContainerExceptionInterface.
 */
class ContainerCacheException extends Exception implements ContainerExceptionInterface {}
