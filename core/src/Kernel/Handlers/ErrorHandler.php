<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel\Handlers;

/**
 * Default PHP error handler for the Sakoo kernel.
 *
 * Registered via set_error_handler() during Kernel::run() when the host application
 * does not supply a custom handler. On invocation it prints the five innermost stack
 * frames (without arguments, to avoid leaking sensitive data) and terminates the
 * process with a formatted error summary containing the error code, message, file,
 * and line number.
 *
 * Because it calls exit() the handler satisfies the never return type and prevents
 * PHP from continuing execution after a non-fatal error that has been escalated to
 * this level.
 */
class ErrorHandler
{
	/**
	 * Prints a backtrace and terminates the process with a human-readable error summary.
	 *
	 * Invoked by PHP's error handler mechanism with the standard four-argument
	 * signature: error level code, error message, source file path, and line number.
	 */
	public function __invoke(string $errorNumber, string $errorString, string $errorFile, string $errorLine): never
	{
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		exit("[$errorNumber] $errorString at $errorFile line $errorLine" . PHP_EOL);
	}
}
