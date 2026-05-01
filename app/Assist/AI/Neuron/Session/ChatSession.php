<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Session;

/**
 * Value object that binds a SessionId to the agent that owns it and derives
 * the canonical chat-history file path for that session.
 *
 * Keeping path derivation here avoids scattered string concatenation across
 * BaseAgent and AgentCommand, and makes it trivial to change the storage layout
 * in one place.
 */
final readonly class ChatSession
{
	private const FILE_PREFIX = 'neuron_';
	private const FILE_EXT = '.chat';

	public function __construct(
		public readonly SessionId $sessionId,
		public readonly string $agentName,
	) {}

	public function filePath(string $storageDir): string
	{
		return $storageDir . '/ai/chat-history/' . self::FILE_PREFIX . $this->agentName . '_' . $this->sessionId->value . self::FILE_EXT;
	}

	public function historyKey(): string
	{
		return $this->agentName . '_' . $this->sessionId->value;
	}
}
