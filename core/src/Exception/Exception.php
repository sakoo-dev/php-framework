<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Exception;

use Exception as BaseException;

/**
 * Base exception class for the Sakoo Framework.
 *
 * All domain-specific and infrastructure exceptions in the framework extend this class,
 * providing a common ancestor for framework-thrown exceptions and enabling catch-all
 * handling at application boundaries without catching PHP's base Exception directly.
 */
class Exception extends BaseException {}
