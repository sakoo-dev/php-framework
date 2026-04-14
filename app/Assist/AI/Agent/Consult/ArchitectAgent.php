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
		return (string) file_get_contents(__DIR__ . '/../../Prompt/Reference/architect.md');
	}

	public function getName(): string
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
		] + $this->neuronToolkitNames();
	}

	public function getExcludedContexts(): array
	{
		return [
			'file://list',
			'prompt://system',
			'project://makefile',
			'project://commands',
			'reference://prompt-engineering',
			'reference://quality-assurance',
			'reference://file-handling',
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
