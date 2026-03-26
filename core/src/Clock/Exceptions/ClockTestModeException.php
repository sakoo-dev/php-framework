<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Clock\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when Clock::setTestNow() is called outside of test mode.
 *
 * Overriding the current time is a test-only capability; calling it in any other
 * kernel mode would introduce non-deterministic behaviour in production or console
 * runs. This exception acts as an explicit guard against accidental misuse.
 */
class ClockTestModeException extends Exception {}
