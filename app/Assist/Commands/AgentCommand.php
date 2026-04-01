<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\Assist\AI\Agent\ChatBotAgent;
use App\Assist\AI\Agent\DataAnalystAgent;
use App\Assist\AI\Agent\DeveloperAgent;
use App\Assist\AI\Agent\ProductManagerAgent;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

class AgentCommand extends Command
{
	/**
	 * @var string[]
	 */
	private array $agents = [
		DataAnalystAgent::class,
		DeveloperAgent::class,
		ProductManagerAgent::class,
		ChatBotAgent::class,
	];

	public static function getName(): string
	{
		return 'agent';
	}

	public static function getDescription(): string
	{
		return 'Talk to AI Agent';
	}

	public function run(Input $input, Output $output): int
	{
		$selectedAgent = $input->radio($this->agents, 'Select an Agent to Talk with:');
		/** @var Agent $agent */
		$agent = $selectedAgent::make();

		// @phpstan-ignore while.alwaysTrue
		while (true) {
			$output->block('Enter Your Prompt:', Output::COLOR_CYAN);
			$prompt = $input->getUserInput();

			$output->block('Processing...', Output::COLOR_CYAN);

			$agentMessage = $agent->chat(new UserMessage($prompt))->getMessage();
			$agentUsage = $agentMessage->getUsage();

			$output->block($agentMessage->getContent() ?? '', Output::COLOR_GREEN);
			$output->block('Input Tokens: ' . ($agentUsage->inputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Output Tokens: ' . ($agentUsage->outputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Total Tokens: ' . ($agentUsage?->getTotal() ?? 'N/A'), Output::COLOR_MAGENTA);
		}

		// @phpstan-ignore deadCode.unreachable
		return Output::SUCCESS;
	}
}
