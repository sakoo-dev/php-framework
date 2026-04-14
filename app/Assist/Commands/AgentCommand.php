<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\Assist\AI\Agent\BaseAgent;
use App\Assist\AI\Agent\ChatBotAgent;
use App\Assist\AI\Agent\Consult\ArchitectAgent;
use App\Assist\AI\Agent\Consult\WorkerAgent;
use App\Assist\AI\Agent\DataAnalystAgent;
use App\Assist\AI\Agent\DeveloperAgent;
use App\Assist\AI\Agent\ProductManagerAgent;
use App\Assist\AI\Mcp\McpElements;
use App\Assist\AI\Neuron\McpAttributeContextProvider;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ChatHistoryException;
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
		WorkerAgent::class,
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
		/** @var class-string<BaseAgent> $selectedClass */
		$selectedClass = $input->radio($this->agents, 'Select an Agent to Talk with:');

		/** @var BaseAgent $agent */
		$agent = $this->buildAgent($selectedClass);

		$mcpContextProvider = new McpAttributeContextProvider(McpElements::class);
		$mcpContextProvider->exclude($agent->getExcludedContexts());
		$agent->withMcpContext($mcpContextProvider);

		// @phpstan-ignore while.alwaysTrue
		while (true) {
			$output->block('Enter Your Prompt:', Output::COLOR_CYAN);
			$prompt = $input->getUserInput();

			$output->block('Processing...', Output::COLOR_CYAN);

			try {
				$agentMessage = $agent->chat(new UserMessage($prompt))->getMessage();
			} catch (ChatHistoryException $e) {
				if (str_contains($e->getMessage(), 'Invalid message sequence at position')) {
					// @phpstan-ignore method.notFound
					$agent->getChatHistory()->removeLastLog();
					$output->block('Chat History Error Fixing ...', Output::COLOR_RED);

					continue;
				}
			}

			/** @phpstan-ignore variable.undefined */
			$agentUsage = $agentMessage->getUsage();
			// @phpstan-ignore variable.undefined
			$output->block('Reasoning: ' . ($agentMessage->getReasoning()?->getContent() ?? 'N/A'), Output::COLOR_YELLOW);
			// @phpstan-ignore variable.undefined
			$output->block($agentMessage->getContent() ?? '', Output::COLOR_GREEN);
			$output->block('Input Tokens: ' . ($agentUsage->inputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Output Tokens: ' . ($agentUsage->outputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Total Tokens: ' . ($agentUsage?->getTotal() ?? 'N/A'), Output::COLOR_MAGENTA);
		}

		// @phpstan-ignore deadCode.unreachable
		return Output::SUCCESS;
	}

	/**
	 * @param class-string<BaseAgent> $class
	 */
	private function buildAgent(string $class): BaseAgent
	{
		if (WorkerAgent::class === $class) {
			return new WorkerAgent(ArchitectAgent::make());
		}

		return $class::make();
	}
}
