<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use NeuronAI\MCP\McpException;
use NeuronAI\MCP\McpTransportInterface;

/**
 * Stdio MCP transport for newline-delimited JSON-RPC messages.
 *
 * Neuron's bundled stdio transport attempts to decode every fread() chunk as a
 * complete JSON document. Large MCP responses such as tools/list are commonly
 * split across multiple chunks, so this transport buffers until a full line is
 * available before decoding.
 */
final class LineDelimitedStdioTransport implements McpTransportInterface
{
	/** @var null|false|resource */
	private mixed $process = null;

	/** @var null|array{0: resource, 1: resource, 2: resource} */
	private ?array $pipes = null;

	private string $buffer = '';
	private string $command;

	/** @var list<string> */
	private array $args;

	/** @var array<string, string> */
	private array $env;

	/**
	 * @param array<string, mixed> $config
	 */
	public function __construct(array $config)
	{
		$this->command = $this->readCommand($config);
		$this->args = $this->readArgs($config['args'] ?? []);
		$this->env = $this->readEnv($config['env'] ?? []);
	}

	public function connect(): void
	{
		$descriptorSpec = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];

		$commandLine = $this->command;

		foreach ($this->args as $arg) {
			$commandLine .= ' ' . escapeshellarg($arg);
		}

		$environment = array_merge($this->systemEnvironment(), $this->env);

		$pipes = [];
		$this->process = proc_open($commandLine, $descriptorSpec, $pipes, null, $environment);

		if (
			!is_resource($this->process)
			|| !isset($pipes[0], $pipes[1], $pipes[2])
			|| !is_resource($pipes[0])
			|| !is_resource($pipes[1])
			|| !is_resource($pipes[2])
		) {
			throw new McpException('Failed to start the MCP server process');
		}

		$this->pipes = [$pipes[0], $pipes[1], $pipes[2]];

		stream_set_write_buffer($pipes[0], 0);
		stream_set_read_buffer($pipes[1], 0);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$status = proc_get_status($this->process);

		if (!$status['running']) {
			$error = stream_get_contents($pipes[2]) ?: '';

			throw new McpException('Process failed to start: ' . $error);
		}
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function send(array $data): void
	{
		if (!is_resource($this->process)) {
			throw new McpException('Process is not running');
		}

		$pipes = $this->pipes();
		$status = proc_get_status($this->process);

		if (!$status['running']) {
			throw new McpException('MCP server process is not running');
		}

		$json = json_encode($data, \JSON_THROW_ON_ERROR);
		$bytesWritten = fwrite($pipes[0], $json . "\n");

		if (false === $bytesWritten || $bytesWritten < strlen($json) + 1) {
			throw new McpException('Failed to write complete request to MCP server');
		}

		fflush($pipes[0]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function receive(): array
	{
		if (!is_resource($this->process)) {
			throw new McpException('Process is not running');
		}

		$pipes = $this->pipes();
		$startTime = microtime(true);
		$timeout = 30.0;

		while (microtime(true) - $startTime < $timeout) {
			if (false !== ($line = $this->readBufferedLine())) {
				if ('' === $line) {
					continue;
				}

				return $this->decodeResponse($line);
			}

			$status = proc_get_status($this->process);

			if (!$status['running']) {
				$error = stream_get_contents($pipes[2]) ?: '';

				throw new McpException('MCP server process has terminated unexpectedly. ' . $error);
			}

			$chunk = fread($pipes[1], 8192);

			if (false !== $chunk && '' !== $chunk) {
				$this->buffer .= $chunk;

				continue;
			}

			usleep(10000);
		}

		throw new McpException('Timeout waiting for response from MCP server');
	}

	public function disconnect(): void
	{
		if (null !== $this->pipes) {
			foreach ($this->pipes as $pipe) {
				if (is_resource($pipe)) {
					fclose($pipe);
				}
			}
		}

		if (is_resource($this->process)) {
			proc_terminate($this->process);
			proc_close($this->process);
		}

		$this->pipes = null;
		$this->process = null;
		$this->buffer = '';
	}

	private function readBufferedLine(): false|string
	{
		$position = strpos($this->buffer, "\n");

		if (false === $position) {
			return false;
		}

		$line = substr($this->buffer, 0, $position);
		$this->buffer = substr($this->buffer, $position + 1);

		return rtrim($line, "\r");
	}

	/**
	 * @return array{0: resource, 1: resource, 2: resource}
	 */
	private function pipes(): array
	{
		if (null === $this->pipes) {
			throw new McpException('Process pipes are not available');
		}

		return $this->pipes;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decodeResponse(string $line): array
	{
		$decoded = json_decode($line, true, 64, \JSON_THROW_ON_ERROR);

		if (!is_array($decoded)) {
			throw new McpException('Invalid MCP response payload');
		}

		$response = [];

		foreach ($decoded as $key => $value) {
			if (!is_string($key)) {
				throw new McpException('Invalid MCP response object');
			}

			$response[$key] = $value;
		}

		return $response;
	}

	/**
	 * @param array<string, mixed> $config
	 */
	private function readCommand(array $config): string
	{
		$command = $config['command'] ?? null;

		if (!is_string($command) || '' === $command) {
			throw new McpException('MCP stdio transport requires a command');
		}

		return $command;
	}

	/**
	 * @return list<string>
	 */
	private function readArgs(mixed $args): array
	{
		if (!is_array($args)) {
			throw new McpException('MCP stdio transport args must be an array');
		}

		$resolved = [];

		foreach ($args as $arg) {
			if (!is_scalar($arg)) {
				throw new McpException('MCP stdio transport args must be scalar values');
			}

			$resolved[] = (string) $arg;
		}

		return $resolved;
	}

	/**
	 * @return array<string, string>
	 */
	private function readEnv(mixed $env): array
	{
		if (!is_array($env)) {
			throw new McpException('MCP stdio transport env must be an array');
		}

		$resolved = [];

		foreach ($env as $key => $value) {
			if (!is_string($key) || !is_scalar($value)) {
				throw new McpException('MCP stdio transport env must be a string map');
			}

			$resolved[$key] = (string) $value;
		}

		return $resolved;
	}

	/**
	 * @return array<string, string>
	 */
	private function systemEnvironment(): array
	{
		return $this->readEnv(getenv());
	}
}
