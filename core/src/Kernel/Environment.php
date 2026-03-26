<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Kernel;

/**
 * Enumerates the deployment environments recognised by the Sakoo kernel.
 *
 * The environment controls diagnostic output and error visibility:
 *
 * - Debug      — intended for local development; enables display_errors and
 *                display_startup_errors so exceptions are visible in the browser
 *                or terminal without requiring a log viewer.
 * - Production — intended for live deployments; suppresses raw error output to
 *                prevent leaking implementation details to end users. All errors
 *                should be captured by structured logging instead.
 */
enum Environment: string
{
	case Debug = 'Debug';

	case Production = 'Production';
}
