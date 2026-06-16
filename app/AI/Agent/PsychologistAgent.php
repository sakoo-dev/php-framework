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

	protected function includedTools(): array
	{
		return [
			new RetrievalTool($this),
		];
	}

	protected function contexts(): array
	{
		return [];
	}

	public function shouldApplyGuardrails(): bool
	{
		return true;
	}
}
