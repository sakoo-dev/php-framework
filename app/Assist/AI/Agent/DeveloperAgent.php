<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\SystemPrompt;

class DeveloperAgent extends BaseAgent
{
	public function instructions(): string
	{
		return (string) new SystemPrompt(
			background: [
				file_get_contents(__DIR__ . '/../Prompt/Skill/software-engineer.md'),
			],
		);
	}

	protected function tools(): array
	{
		return $this->mcpTools();
	}
}
