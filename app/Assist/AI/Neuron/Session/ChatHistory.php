<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Session;

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
	public function removeLastLog(): void
	{
		$this->history = array_slice($this->history, 0, -2);
		$this->updateFile();
	}
}
