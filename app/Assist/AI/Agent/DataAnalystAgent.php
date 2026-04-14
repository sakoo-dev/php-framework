<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

class DataAnalystAgent extends BaseAgent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Skill/data-analyst.md');
	}

	public function getName(): string
	{
		return 'dataanalyst';
	}

	public function getExcludedTools(): array
	{
		return ['write_file', 'remove_file'] + $this->neuronToolkitNames();
	}

	public function getExcludedContexts(): array
	{
		return [
			'file://list',
			'prompt://system',
			'project://makefile',
			'project://commands',
			'reference://architecture',
			'reference://conventions',
			'reference://prompt-engineering',
			'reference://quality-assurance',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
