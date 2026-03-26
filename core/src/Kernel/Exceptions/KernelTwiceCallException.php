<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when Kernel::prepare() is called a second time within the same process.
 *
 * The Kernel is a process-scoped singleton; instantiating it more than once would
 * produce two separate container and profiler instances, breaking the assumption
 * that there is exactly one authoritative service registry per process. This
 * exception guards against accidental double-initialisation in integration tests,
 * long-running Swoole workers, or any other scenario where boot code might be
 * executed more than once.
 */
class KernelTwiceCallException extends Exception {}
