<?php

declare(strict_types=1);

use App\Assist\Commands\AgentCommand;
use App\Assist\Commands\ChatBotSeederCommand;
use App\Assist\Commands\ExampleCommand;
use App\Assist\Commands\McpServerCommand;
use Sakoo\Framework\Core\Commands\ContainerCacheCommand;
use Sakoo\Framework\Core\Commands\DevCommand;
use Sakoo\Framework\Core\Commands\DocGenCommand;
use Sakoo\Framework\Core\Commands\Watcher\WatchCommand;
use Sakoo\Framework\Core\Commands\ZenCommand;
use Sakoo\Framework\Core\Console\Application;
use Sakoo\Framework\Core\Console\Command;
use System\Path\Path;

$docOutputDir = Path::getRootDir() . '/.github/wiki';

/** @var Command[] $commands */
$commands = [
	resolve(ExampleCommand::class),
	resolve(AgentCommand::class),
	resolve(ChatBotSeederCommand::class),
	resolve(ZenCommand::class),
	resolve(WatchCommand::class),
	resolve(DevCommand::class),
	resolve(McpServerCommand::class),
	makeInstance(ContainerCacheCommand::class, [container()]),
	makeInstance(DocGenCommand::class, ["$docOutputDir/Home.md", "$docOutputDir/_Sidebar.md", "$docOutputDir/_Footer.md"]),
];

/** @var Application $application */
$application = resolve(Application::class);
$application->addCommands($commands);
$application->setDefaultCommand(ZenCommand::class);

return $application;
