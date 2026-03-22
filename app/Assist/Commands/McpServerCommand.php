<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\Assist\AI\Mcp\McpServer;
use Mcp\Server\Transport\StdioTransport;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

/**
 * You can test command using below comand:
 * ```npx [at]modelcontextprotocol/inspector ./bin/mcp```
 */
class McpServerCommand extends Command
{
	public static function getName(): string
	{
		return 'mcp:run';
	}

	public static function getDescription(): string
	{
		return 'Creates a MCP server in the Sakoo PHP Framework Context';
	}

	public function run(Input $input, Output $output): int
	{
		try {
			McpServer::factory()->run(new StdioTransport());
		} catch (\Throwable $e) {
			fwrite(STDERR, '[CRITICAL ERROR] ' . $e->getMessage() . "\n");

			return Output::ERROR;
		}

		return Output::SUCCESS;
	}
}
