<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron;

use Psr\Clock\ClockInterface;
use Psr\Log\AbstractLogger;
use Sakoo\Framework\Core\Exception\Exception;
use Sakoo\Framework\Core\FileSystem\Disk;
use Sakoo\Framework\Core\FileSystem\File;
use Sakoo\Framework\Core\Logger\LogFormatter;
use System\Path\Path;

/**
 * PSR-3 logger that writes to the AI-specific daily log directory.
 *
 * Mirrors the behaviour of the framework's FileLogger but redirects
 * output to `storage/ai/logs/{Y-m-d}.log` instead of `storage/logs/`,
 * keeping all AI / agent / MCP activity isolated from the main
 * application log stream.
 *
 * Log format (via {@see LogFormatter}):
 *   [{ISO-8601}] [{LEVEL}] [{Mode} {Environment}] - {message}
 */
final class AiLogger extends AbstractLogger
{
	public function __construct(private readonly ClockInterface $clock) {}

	/**
	 * @param array<mixed> $context
	 *
	 * @throws \Throwable when the log file cannot be written
	 */
	public function log(mixed $level, string|\Stringable $message, array $context = []): void
	{
		$env = kernel()->getEnvironment()->value;
		$mode = kernel()->getMode()->value;

		$levelStr = is_string($level) ? $level : 'debug';
		$line = (string) new LogFormatter($levelStr, $message, $mode, $env);
		$written = File::open(Disk::Local, $this->getLogFileName())->append($line . PHP_EOL);

		throwUnless($written, new Exception('Failed to write AI log file.'));
	}

	private function getLogFileName(): string
	{
		return Path::getStorageDir() . '/ai/logs/' . $this->clock->now()->format('Y-m-d') . '.log';
	}
}
