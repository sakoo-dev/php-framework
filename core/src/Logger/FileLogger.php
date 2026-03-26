<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Logger;

use Psr\Log\AbstractLogger;
use Sakoo\Framework\Core\Clock\Clock;
use Sakoo\Framework\Core\Exception\Exception;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Path\Path;

/**
 * PSR-3 logger implementation that persists log entries to daily rotating files.
 *
 * Extends Psr\Log\AbstractLogger so all eight convenience methods (debug, info,
 * warning, etc.) are available out of the box — only log() needs to be implemented.
 *
 * Each log entry is formatted by LogFormatter and appended to a file whose path
 * follows the pattern {logs_dir}/{Y/m/d}.log, rotating automatically at midnight.
 * The current environment (Debug/Production) and mode (Test/Console/HTTP) are
 * embedded in every entry for contextual filtering.
 *
 * If the filesystem write fails, an Exception is thrown rather than silently
 * swallowing the failure, ensuring observability issues surface immediately.
 */
class FileLogger extends AbstractLogger
{
	/**
	 * Formats the log entry and appends it to today's rotating log file.
	 *
	 * @param string $level PSR-3 log level string (e.g. 'debug', 'error')
	 *
	 * @throws \Exception|\Throwable when the log file cannot be written
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$log = $this->getFormattedLog($level, $message);
		$isWritten = $this->writeToFile($log);
		throwUnless($isWritten, new Exception('Failed to write log file.'));
	}

	/**
	 * Builds the formatted log line by delegating to LogFormatter, injecting the
	 * current environment and mode values from the kernel.
	 */
	private function getFormattedLog(string $level, string|\Stringable $message): string
	{
		$env = kernel()->getEnvironment()->value;
		$mode = kernel()->getMode()->value;

		return (string) new LogFormatter($level, $message, $mode, $env);
	}

	/**
	 * Appends $log to today's log file on the local disk, creating intermediate
	 * directories as required. Returns true on success, false on failure.
	 */
	private function writeToFile(string $log): bool
	{
		return File::open(Disk::Local, $this->getLogFileName())
			->append($log . PHP_EOL);
	}

	/**
	 * Returns the absolute path of today's log file, partitioned by year, month,
	 * and day under the configured logs directory.
	 */
	private function getLogFileName(): string
	{
		return Path::getLogsDir() . '/' . (new Clock())->now()->format('Y/m/d') . '.log';
	}
}
