<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Assert\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown by Assert when a single assertion fails immediately.
 *
 * Carries the human-readable message describing which assertion was violated and
 * what value triggered the failure. Extends the framework base Exception so it can
 * be caught at application boundaries alongside other framework exceptions.
 */
class InvalidArgumentException extends Exception {}
