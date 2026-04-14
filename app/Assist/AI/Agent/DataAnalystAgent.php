<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

class DataAnalystAgent extends BaseAgent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Skill/data-analyst.md');
	}

	protected function getName(): string
	{
		return 'dataanalyst';
	}

	public function getExcludedTools(): array
	{
		return ['write_file', 'remove_file'];
	}

	public function getExcludedContexts(): array
	{
		return [
			'resource:file://list',
			'resource:prompt://system',
			'resource:project://makefile',
			'resource:project://commands',
			'resource:reference://architecture',
			'resource:reference://conventions',
			'resource:reference://prompt-engineering',
			'resource:reference://quality-assurance',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
