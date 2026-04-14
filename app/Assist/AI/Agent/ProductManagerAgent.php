<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

class ProductManagerAgent extends BaseAgent
{
	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Prompt/Skill/product-manager.md');
	}

	public function getName(): string
	{
		return 'productmanager';
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
			'project://structure',
			'project://info',
			'project://makefile',
			'project://commands',
			'reference://architecture',
			'reference://conventions',
			'reference://prompt-engineering',
			'reference://file-handling',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}
}
