<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;
use Sakoo\Framework\Core\Kernel\Kernel;

/**
 * Thrown when {@see Kernel::destroy()} is called
 * outside of Test mode.
 *
 * Destroying the kernel in Console or HTTP mode would leave the application
 * in a broken state where all global helpers (kernel(), container(), resolve())
 * throw {@see KernelIsNotStartedException}. This exception prevents accidental
 * destruction of a running production or console kernel.
 */
class IllegalKernelDestroyException extends Exception {}
