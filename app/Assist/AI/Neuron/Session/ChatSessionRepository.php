<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Session;

use System\Path\Path;

/**
 * Scans the chat-history storage directory to discover existing sessions for
 * a given agent name.
 *
 * Each session file is named `neuron_{agentName}_{sessionId}.chat`. This
 * repository parses that convention to reconstruct ChatSession instances,
 * allowing AgentCommand to present the user with a list of resumable sessions
 * without coupling storage details to the command layer.
 */
final class ChatSessionRepository
{
	private const FILE_PREFIX = 'neuron_';
	private const FILE_EXT = '.chat';

	/**
	 * Returns all existing sessions for the given agent, ordered newest-first
	 * by file modification time.
	 *
	 * @return ChatSession[]
	 */
	public function findByAgent(string $agentName): array
	{
		$dir = Path::getStorageDir() . '/ai/chat-history';

		if (!is_dir($dir)) {
			return [];
		}

		$pattern = $dir . '/' . self::FILE_PREFIX . $agentName . '_*' . self::FILE_EXT;
		$files = glob($pattern);

		if (false === $files || [] === $files) {
			return [];
		}

		usort($files, static fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

		return array_map(
			fn (string $file) => $this->parseSessionFile($file, $agentName),
			$files,
		);
	}

	private function parseSessionFile(string $filePath, string $agentName): ChatSession
	{
		$basename = basename($filePath, self::FILE_EXT);
		$prefix = self::FILE_PREFIX . $agentName . '_';
		$sessionIdValue = substr($basename, strlen($prefix));

		return new ChatSession(
			sessionId: SessionId::fromString($sessionIdValue),
			agentName: $agentName,
		);
	}
}
