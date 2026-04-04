<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel\Handlers;

/**
 * Default PHP error handler for the Sakoo kernel.
 *
 * Registered via set_error_handler() during Kernel::run() when the host application
 * does not supply a custom handler. Converts PHP errors into ErrorException instances
 * so they integrate with try/catch flows and do not silently continue execution.
 *
 * Respects the @ error-suppression operator: when error_reporting() returns 0 the
 * handler returns false and lets PHP handle the error internally (this is the
 * standard convention for suppressed errors).
 */
class ErrorHandler
{
	/**
	 * Converts a PHP error into an ErrorException.
	 *
	 * Invoked by PHP's error handler mechanism with the standard four-argument
	 * signature: error level code, error message, source file path, and line number.
	 *
	 * @throws \ErrorException always, unless the error was suppressed with @
	 */
	public function __invoke(int $errorNumber, string $errorString, string $errorFile, int $errorLine): bool
	{
		if (!(error_reporting() & $errorNumber)) {
			return false;
		}

		throw new \ErrorException($errorString, 0, $errorNumber, $errorFile, $errorLine);
	}
}
