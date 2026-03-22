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

			$message = $agent->chat(new UserMessage($prompt))->getMessage()->getContent() ?? '';
			$output->block($message, Output::COLOR_GREEN);
		}

		// @phpstan-ignore deadCode.unreachable
		return Output::SUCCESS;
	}
}
