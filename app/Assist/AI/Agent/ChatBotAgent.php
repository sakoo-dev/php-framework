<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

class ChatBotAgent extends BaseAgent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Skill/chatbot.md');
	}

	public function getName(): string
	{
		return 'chatbot';
	}

	public function getExcludedTools(): array
	{
		return ['write_file', 'remove_file', 'sakoo_exec', 'test_run', 'test_coverage', 'check_code'] + $this->neuronToolkitNames();
	}

	public function getExcludedContexts(): array
	{
		return [
			'file://list',
			'prompt://system',
			'project://makefile',
			'reference://prompt-engineering',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
