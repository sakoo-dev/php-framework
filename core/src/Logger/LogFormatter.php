<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Logger;

use Sakoo\Framework\Core\Clock\Clock;

/**
 * Formats a single PSR-3 log entry into a structured, human-readable string.
 *
 * The rendered format is:
 *   [{ISO-8601 datetime}] [{LEVEL}] [{Mode} {Environment}] - {message}
 *
 * For example:
 *   [2024-06-01T12:00:00+00:00] [ERROR] [HTTP Production] - Payment gateway timeout
 *
 * Implemented as an immutable readonly class: all state is captured at construction
 * time and the formatted string is produced lazily via __toString(). This makes
 * instances safe to pass around and cast to string at any point without side effects.
 *
 * The current instant is obtained from Clock so the formatter remains compatible
 * with time-pinning in test mode.
 */
readonly class LogFormatter
{
	/**
	 * @param string $level PSR-3 log level string (e.g. 'debug', 'error')
	 * @param string $mode  Kernel mode value (e.g. 'HTTP', 'Console', 'Test')
	 * @param string $env   Kernel environment value (e.g. 'Debug', 'Production')
	 */
	public function __construct(
		private string $level,
		private string|\Stringable $message,
		private string $mode,
		private string $env,
	) {}

	/**
	 * Renders the log entry as a single line containing the ISO-8601 timestamp,
	 * uppercased level, mode, environment, and the message.
	 */
	public function __toString()
	{
		$dateTime = (new Clock())->now()->format(\DateTimeInterface::ATOM);

		return "[$dateTime] [" . strtoupper($this->level) . "] [$this->mode $this->env] - $this->message";
	}
}
