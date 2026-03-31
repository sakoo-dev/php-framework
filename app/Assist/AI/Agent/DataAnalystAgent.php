<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Tools\ToolInterface;

class DataAnalystAgent extends BaseAgent
{
	private const TOOL_ALLOWLIST = [
		'read_log_entries',
		'search_docs',
		'browser_logs',
		'get_absolute_url',
		'count_prompt_tokens',
	];

	public function instructions(): string
	{
		return (string) new SystemPrompt(
			background: [
				file_get_contents(__DIR__ . '/../Prompt/Skill/data-analyst.md'),
			],
		);
	}

	protected function tools(): array
	{
		return array_values(
			array_filter(
				$this->mcpTools(),
				fn (ToolInterface $tool): bool => in_array($tool->getName() ?? '', self::TOOL_ALLOWLIST, true)
			)
		);
	}
}
