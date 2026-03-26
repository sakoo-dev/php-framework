<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core;

use Sakoo\Framework\Core\Doc\Attributes\DontDocument;

/**
 * Compile-time identity constants for the Sakoo Framework.
 *
 * Provides the canonical framework name, current semantic version string, and
 * maintainer attribution consumed by VersionCommand, ZenCommand, and any other
 * output that needs to identify the framework. Centralising these values here
 * ensures that a version bump requires only a single change rather than updates
 * scattered across multiple files.
 *
 * The class carries the DontDocument attribute so it is excluded from the
 * auto-generated API reference.
 */
#[DontDocument]
class Constants
{
	/** The human-readable marketing name of the framework. */
	final public const string FRAMEWORK_NAME = 'Sakoo Framework';

	/** The current semantic version string (MAJOR.MINOR.PATCH). */
	final public const string FRAMEWORK_VERSION = '0.6.1';

	/** The name of the primary framework maintainer. */
	final public const string MAINTAINER = 'Pouya Asgharnejad Tehran';
}
