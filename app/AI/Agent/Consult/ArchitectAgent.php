<?php

declare(strict_types=1);

namespace App\AI\Agent\Consult;

use App\AI\Agent\Agent;
use App\AI\Neuron\Schema\ArchitectDirective;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\AIProviderInterface;

final class ArchitectAgent extends Agent
{
	protected function provider(): AIProviderInterface
	{
		// @phpstan-ignore-next-line
		return resolve($this->supportsThinking() ? 'ai.gapgpt.claude.opus.thinking' : 'ai.gapgpt.claude.opus');
	}

	protected function supportsThinking(): bool
	{
		return true;
	}

	protected function agentInstructions(): string
	{
		return (string) file_get_contents(__DIR__ . '/../../Prompt/Role/architect.md');
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
			'skill://prompt-engineering',
			'skill://quality-assurance',
			'skill://file-handling',
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
