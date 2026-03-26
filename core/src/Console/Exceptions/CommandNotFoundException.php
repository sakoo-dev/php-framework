<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a command name cannot be found in the Application's registry.
 *
 * Raised by Application::setDefaultCommand() when the provided class has not
 * been registered via addCommand() first, preventing an undefined-key error
 * at dispatch time.
 */
class CommandNotFoundException extends Exception {}
