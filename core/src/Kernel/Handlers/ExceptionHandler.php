<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel\Handlers;

/**
 * Default PHP exception handler for the Sakoo kernel.
 *
 * Registered via set_exception_handler() during Kernel::run() when the host
 * application does not supply a custom handler. On invocation it prints the five
 * innermost stack frames (without arguments, to avoid leaking sensitive data) and
 * re-throws the original exception so the full stack trace is ultimately surfaced
 * by PHP's fatal-error mechanism or a higher-level error reporter.
 *
 * Re-throwing rather than calling exit() preserves the original exception type and
 * message for logging infrastructure that wraps the handler chain.
 */
class ExceptionHandler
{
	/**
	 * Prints a backtrace and re-throws the given exception so it propagates to
	 * PHP's fatal-error handler or any wrapping observer.
	 *
	 * @throws \Throwable always re-throws $exception
	 */
	public function __invoke(\Throwable $exception): never
	{
		debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		throw $exception;
	}
}
