<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

class ProductManagerAgent extends BaseAgent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Skill/product-manager.md');
	}

	protected function getName(): string
	{
		return 'productmanager';
	}

	public function getExcludedTools(): array
	{
		return ['write_file', 'remove_file', 'sakoo_exec', 'test_run', 'test_coverage', 'check_code'];
	}

	public function getExcludedContexts(): array
	{
		return [
			'resource:file://list',
			'resource:prompt://system',
			'resource:project://structure',
			'resource:project://info',
			'resource:project://makefile',
			'resource:project://commands',
			'resource:reference://architecture',
			'resource:reference://conventions',
			'resource:reference://prompt-engineering',
			'resource:reference://file-handling',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
