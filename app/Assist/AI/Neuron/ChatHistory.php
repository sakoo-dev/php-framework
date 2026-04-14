<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron;

use NeuronAI\Chat\History\FileChatHistory;

class ChatHistory extends FileChatHistory
{
	public function removeLastLog(): void
	{
		$this->history = array_slice($this->history, 0, -2);
		$this->updateFile();
	}
}
