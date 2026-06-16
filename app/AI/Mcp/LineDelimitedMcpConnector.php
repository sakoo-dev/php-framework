<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use NeuronAI\MCP\McpClient;
use NeuronAI\MCP\McpConnector;

final class LineDelimitedMcpConnector extends McpConnector
{
	protected function client(): McpClient
	{
		return $this->client ??= new McpClient([
			'transport' => new LineDelimitedStdioTransport($this->config),
		]);
	}
}
