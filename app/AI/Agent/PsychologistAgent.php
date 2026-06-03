<?php

declare(strict_types=1);

namespace App\AI\Agent;

use App\AI\Neuron\Tool\RetrievalTool;

class PsychologistAgent extends Agent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Role/psychologist.md');
	}

	public function getName(): string
	{
		return 'psychologist';
	}

	public function getExcludedTools(): array
	{
		$result = [];

		foreach ($this->availableTools() as $tool) {
			if (RetrievalTool::NAME !== $tool->getName()) {
				$result[] = $tool->getName();
			}
		}

		return $result;
	}

	public function getExcludedContexts(): array
	{
		return $this->availableContexts();
	}
}
