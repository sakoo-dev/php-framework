<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

class DeveloperAgent extends BaseAgent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Skill/software-engineer.md');
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
			'reference://prompt-engineering',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
