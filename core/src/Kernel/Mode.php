<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel;

/**
 * Enumerates the runtime modes in which the Sakoo kernel can operate.
 *
 * The mode determines which parts of the framework are active and influences
 * several framework-level behaviours:
 *
 * - Test     — activated by the test runner; enables Clock::setTestNow(), routes
 *              log output to the temporary test directory, and turns on display_errors.
 * - Console  — activated when the application is invoked from the CLI (e.g. running
 *              console commands or queue workers).
 * - HTTP     — activated when serving web requests; the standard production mode
 *              for handling incoming HTTP traffic.
 */
enum Mode: string
{
	case Test = 'Test';

	case Console = 'Console';

	case HTTP = 'Http';
}
