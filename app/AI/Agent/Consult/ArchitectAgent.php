<?php

declare(strict_types=1);

namespace App\AI\Agent\Consult;

use App\AI\Agent\Agent;
use App\AI\Neuron\Schema\ArchitectDirective;
use App\AI\Neuron\Tool\PromptFetchTool;
use App\AI\Neuron\Tool\ResourceFetchTool;
use App\AI\Neuron\Tool\RetrievalTool;
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

	protected function includedTools(): array
	{
		return [
			...$this->mcpTools()->exclude([])->tools(),
			ResourceFetchTool::make($this->mcpElementsClass()),
			PromptFetchTool::make($this->mcpElementsClass()),
			new RetrievalTool($this),
		];
	}

	protected function contexts(): array
	{
		return [
			'project://structure',
			'project://info',
			'skill://architecture',
			'skill://conventions',
			'skill://sakoo-identity',
			'skill://security-checklist',
		];
	}

	public function consult(string $context): ArchitectDirective
	{
		// @phpstan-ignore return.type
		return $this->structured(new UserMessage($context), ArchitectDirective::class);
	}
}
