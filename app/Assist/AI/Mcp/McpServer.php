<?php

declare(strict_types=1);

namespace App\Assist\AI\Mcp;

use Mcp\Schema\Icon;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use System\Path\Path;

class McpServer
{
	public static function factory(): Server
	{
		$discoverPath = Path::getAppDir() ?: __DIR__;
		$sessionPath = Path::getStorageDir() . '/ai/mcp-sessions';
		$icon = 'data:image/png;base64,' . base64_encode(file_get_contents(Path::getRootDir() . '/.github/static/logo-dark.png') ?: '');

		return Server::builder()
			->setServerInfo(
				name: 'Sakoo PHP Framework MCP Server',
				version: '1.0.0',
				description: 'This MCP Server provides all of necessary Components to communicate with LLMs.',
				websiteUrl: 'https://sakoo.dev',
				icons: [new Icon($icon, 'image/png', ['96x96'])],
			)
			->setSession(new FileSessionStore($sessionPath))
			->setDiscovery($discoverPath)
			->build();
	}
}
