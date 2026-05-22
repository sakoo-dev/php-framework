<?php

declare(strict_types=1);

namespace App\AI\Agent;

class DeveloperAgent extends Agent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Role/software-engineer.md');
	}

	public function getName(): string
	{
		return 'developer';
	}

	public function getExcludedTools(): array
	{
		return [];
	}

	public function getExcludedContexts(): array
	{
		return [
			'skill://prompt-engineering',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
