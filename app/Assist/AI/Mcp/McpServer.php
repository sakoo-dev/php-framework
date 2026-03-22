<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use System\Path\Path;

class McpServer
{
	public static function factory(): Server
	{
		$discoverPath = Path::getAppDir() ?: __DIR__;
		$sessionPath = Path::getStorageDir() . '/ai/mcp-sessions';

		return Server::builder()
			->setServerInfo('PHP MCP Server', '1.0.0')
			->setSession(new FileSessionStore($sessionPath))
			->setDiscovery($discoverPath)
			->build();
	}
}
