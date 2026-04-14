<?php

declare(strict_types=1);

namespace App\Assist\AI\Agent\Consult;

use App\Assist\AI\Agent\BaseAgent;
use App\Assist\AI\Neuron\ArchitectDirective;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;

final class ArchitectAgent extends BaseAgent
{
	protected function provider(): AIProviderInterface
	{
		// @phpstan-ignore-next-line
		return resolve('ai.provider.opus');
	}

	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../Reference/architect.md');
	}

	protected function getName(): string
	{
		return 'architect';
	}

	public function getExcludedTools(): array
	{
		return [
			'write_file',
			'remove_file',
			'sakoo_exec',
			'test_run',
			'test_coverage',
			'check_code',
			'consult_architect',
		];
	}

	public function getExcludedContexts(): array
	{
		return [
			'resource:file://list',
			'resource:prompt://system',
			'resource:project://makefile',
			'resource:project://commands',
			'resource:reference://prompt-engineering',
			'resource:reference://quality-assurance',
			'resource:reference://file-handling',
			'prompt:dev_task',
			'prompt:review_file',
		];
	}

	public function consult(string $context): ArchitectDirective
	{
		// @phpstan-ignore return.type
		return $this->structured(new UserMessage($context), ArchitectDirective::class);
	}
}
