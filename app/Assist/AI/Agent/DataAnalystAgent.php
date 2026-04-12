<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\SystemPrompt;

class DataAnalystAgent extends BaseAgent
{
	protected function instructions(): string
	{
		return (string) new SystemPrompt([file_get_contents(__DIR__ . '/../Prompt/Skill/data-analyst.md')]);
	}

	protected function getName(): string
	{
		return 'dataanalyst';
	}
}
