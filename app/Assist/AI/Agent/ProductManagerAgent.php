<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent;

use NeuronAI\Agent\SystemPrompt;

class ProductManagerAgent extends BaseAgent
{
	protected function instructions(): string
	{
		return (string) new SystemPrompt([file_get_contents(__DIR__ . '/../Prompt/Skill/product-manager.md')]);
	}

	protected function getName(): string
	{
		return 'productmanager';
	}
}
