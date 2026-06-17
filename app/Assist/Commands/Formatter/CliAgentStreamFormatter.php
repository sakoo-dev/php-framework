<?php

declare(strict_types=1);

namespace App\Assist\Commands\Formatter;

use NeuronAI\Chat\Messages\Stream\Chunks\ReasoningChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\StreamChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolCallChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolResultChunk;
use Sakoo\Framework\Core\Console\Output;

class CliAgentStreamFormatter
{
	public function __construct(private Output $output) {}

	public function format(?StreamChunk $chunk, ?StreamChunk $previousChunk): void
	{
		if (is_null($chunk)) {
			//			$this->output->block('[Empty Response]', Output::COLOR_RED);

			return;
		}

		if ($this->isNewSectionBlock($previousChunk, $chunk)) {
			$this->output->newLine();
		}

		if ($chunk instanceof ToolCallChunk) {
			$this->output->block('🛠 ⏳  Calling tool: ' . $chunk->tool->getName() . ' (' . json_encode($chunk->tool->getInputs()) . ')', Output::COLOR_MAGENTA);
			//			$this->output->block('Input: ' . json_encode($chunk->tool->getInputs()), Output::COLOR_MAGENTA);

			return;
		}

		if ($chunk instanceof ToolResultChunk) {
			$this->output->block('🛠 ✅  Tool Completed: ' . $chunk->tool->getName(), Output::COLOR_MAGENTA);
			//			$this->output->block('Tool Result: ' . $chunk->tool->getResult(), Output::COLOR_MAGENTA);

			return;
		}

		if ($chunk instanceof ReasoningChunk) {
			//			$this->output->text(((!$previousChunk instanceof ReasoningChunk) ? 'Reasoning: ' : '') . $chunk->content, Output::COLOR_YELLOW);
			if (!$previousChunk instanceof ReasoningChunk) {
				$this->output->block('🤔  Thinking... ', Output::COLOR_YELLOW);
			}

			return;
		}

		if ($chunk instanceof TextChunk) {
			$this->output->text(((!$previousChunk instanceof TextChunk) ? '💎  Result Generated: ' : '') . $chunk->content, Output::COLOR_GREEN);
		}
	}

	private function isNewSectionBlock(?StreamChunk $previousChunk, StreamChunk $chunk): bool
	{
		return !is_null($previousChunk) && $chunk::class !== $previousChunk::class;
	}
}
