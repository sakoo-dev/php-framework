<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use Mcp\Server\Transport\StdioTransport;
use Psr\Log\LoggerInterface;
use Sakoo\AI\Mcp\McpServer;
use Sakoo\AI\Mcp\McpServerDto;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use System\AI\ProjectContext;

/**
 * You can test command using below command:
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
			/**
			 * @var LoggerInterface $logger
			 *
			 * @phpstan-ignore argument.type
			 */
			$logger = resolve('logger.ai');
			$context = resolve(ProjectContext::class);

			$dto = new McpServerDto(
				path: $context->appDir() ?: __DIR__,
				name: 'Sakoo PHP Framework MCP Server',
				version: '1.0.0',
				description: 'This MCP Server provides all of necessary Components to communicate with LLMs.',
				websiteUrl: 'https://sakoo.dev',
				icons: 'https://avatars.githubusercontent.com/u/86832974?s=200&v=4',
				sessionPath: $context->storageDir() . '/ai/mcp-sessions',
				logger: $logger,
			);

			McpServer::factory($dto)->run(new StdioTransport(logger: $logger));
		} catch (\Throwable $e) {
			fwrite(STDERR, '[CRITICAL ERROR] ' . $e->getMessage() . "\n");

			return Output::ERROR;
		}

		return Output::SUCCESS;
	}
}
