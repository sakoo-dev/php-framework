<?php

declare(strict_types=1);

namespace App\AI\Neuron\Session;

use NeuronAI\Chat\History\FileChatHistory;

/**
 * Extends NeuronAI's FileChatHistory with a correction mechanism for invalid
 * message sequences that can arise after interrupted sessions.
 *
 * The key passed at construction should be the compound `{agentName}_{sessionId}`
 * string produced by ChatSession::historyKey(), giving each session its own
 * isolated file while remaining compatible with the parent's file-naming scheme.
 */
class ChatHistory extends FileChatHistory
{
	/**
	 * Removes the last N messages from the persisted history.
	 *
	 * Pass 1 to drop only the trailing assistant message (e.g. an orphaned
	 * tool_use block with no matching tool_result).  Pass 2 (the default) to
	 * discard a complete user+assistant turn, which is the correct recovery
	 * strategy for an invalid-sequence error after an interrupted session.
	 */
	public function removeLastLog(int $count = 2): void
	{
		$this->history = array_slice($this->history, 0, -$count);
		$this->updateFile();
	}
}
