<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when kernel() or Kernel::getInstance() is called before Kernel::prepare()
 * and Kernel::run() have completed.
 *
 * Any code that resolves the kernel or the container during the bootstrap phase —
 * before the singleton instance is populated — will receive this exception rather
 * than a silent null-dereference. It serves as an explicit signal that the
 * application boot order has been violated.
 */
class KernelIsNotStartedException extends Exception {}
