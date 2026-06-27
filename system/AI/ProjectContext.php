<?php

declare(strict_types=1);

namespace System\AI;

use Sakoo\AI\Mcp\ProjectContextInterface;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Finder\FileFinder;
use Sakoo\Framework\Core\Finder\Makefile;
use Sakoo\Framework\Core\Kernel\Kernel;
use System\Path\Path;

/**
 * ProjectContextInterface implementation for the Sakoo framework.
 *
 * Delegates everything to sakoo/core internals (FileFinder, Kernel, Path,
 * Makefile, Application) so McpElements and McpShell stay free of those imports.
 *
 * This class lives in app/ (not in the extractable AI package) because it
 * deliberately couples to sakoo/core.  When shipping the AI package as a
 * standalone composer library, you supply a different implementation
 * (e.g. LaravelProjectContext) and bind it in your service container.
 */
final class ProjectContext implements ProjectContextInterface
{
	/** @var null|array<int, array{name: string, desc: string}> */
	private ?array $cachedCommands = null;

	public function rootDir(): string
	{
		return (string) Path::getRootDir();
	}

	public function promptDir(): string
	{
		return (string) Path::getAppDir() . '/AI/Prompt';
	}

	public function storageDir(): string
	{
		return (string) Path::getStorageDir();
	}

	public function logsDir(): string
	{
		return (string) Path::getLogsDir();
	}

	public function appDir(): string
	{
		return (string) Path::getAppDir();
	}

	public function systemDir(): string
	{
		return (string) Path::getSystemDir();
	}

	public function coreDir(): string
	{
		return (string) Path::getCoreDir();
	}

	public function vendorDir(): string
	{
		return (string) Path::getVendorDir();
	}

	public function listFiles(string $directory, string $glob = '', int $limit = 500): array
	{
		$finder = (new FileFinder($directory))
			->ignoreDotFiles()
			->ignoreVCS()
			->ignoreVCSIgnored()
			->limit($limit);

		if ('' !== $glob) {
			$finder->pattern($glob);
		}

		return $finder->find();
	}

	public function grepFiles(string $pattern, string $directory = '', int $limit = 100): array
	{
		$result = FileFinder::grep($pattern, $directory, $limit);
		$matches = [];

		foreach ($result->matches as $match) {
			$matches[] = ['file' => $match->file, 'line' => $match->line, 'text' => $match->text];
		}

		return $matches;
	}

	public function makefileTargets(): array
	{
		$path = $this->rootDir() . '/Makefile';

		if (!is_file($path)) {
			return [];
		}

		$raw = (new Makefile($path))->getTargets();
		$result = [];

		foreach ($raw as $name => $descArr) {
			$result[] = ['name' => (string) $name, 'desc' => implode(' ', (array) $descArr)];
		}

		return $result;
	}

	public function registeredCommands(): array
	{
		if (null !== $this->cachedCommands) {
			return $this->cachedCommands;
		}

		/** @var Application $application */
		$application = require $this->appDir() . '/Assist/Bootstrap.php';

		/** @var array<int, array{name: string, desc: string}> $list */
		$list = array_map(
			fn (Command $cmd): array => ['name' => $cmd::getName(), 'desc' => $cmd::getDescription()],
			$application->getCommands(),
		);

		$this->cachedCommands = array_values($list);

		return $this->cachedCommands;
	}

	public function runtimeInfo(): array
	{
		$kernel = Kernel::getInstance();

		return [
			'mode' => $kernel->getMode()->value,
			'env' => $kernel->getEnvironment()->value,
			'replica' => $kernel->getReplicaId(),
			'paths' => [
				'root' => $this->rootDir(),
				'storage' => $this->storageDir(),
				'logs' => $this->logsDir(),
				'vendor' => $this->vendorDir(),
				'app' => $this->appDir(),
				'system' => $this->systemDir(),
			],
		];
	}

	public function guardPath(string $path): string
	{
		return FileFinder::guard($path);
	}

	public function getMcpArgs(): array
	{
		return ['command' => 'php', 'args' => [$this->rootDir() . '/assist', 'mcp:run']];
	}
}
