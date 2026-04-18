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
use App\Assist\AI\Mcp\McpContextProvider;
use App\Assist\AI\Mcp\McpElements;
use App\Assist\AI\Mcp\McpTokenObserver;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ChatHistoryException;
use NeuronAI\Observability\LogObserver;
use Psr\Log\LoggerInterface;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

class AgentCommand extends Command
{
	/** @var string[] */
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

		$mcpContextProvider = new McpContextProvider(McpElements::class);
		$mcpContextProvider = $mcpContextProvider->exclude($agent->getExcludedContexts());
		$agent->withMcpContext($mcpContextProvider);

		/** @var McpTokenObserver $tokenObserver */
		$tokenObserver = resolve(McpTokenObserver::class);

		/**
		 * @var LoggerInterface $logger
		 *
		 * @phpstan-ignore argument.type
		 */
		$logger = resolve('logger.ai');
		$agent->observe(new LogObserver($logger));

		$output->block([
			"\t\t=======================",
			"\t\t=========",
			' =======================',
		], Output::COLOR_CYAN);

		$output->block('Welcome to Sakoo ' . strtoupper($agent->getName()) . ' Agent!', Output::COLOR_RED);

		// @phpstan-ignore while.alwaysTrue
		while (true) {
			$output->block('Enter Your Prompt:', Output::COLOR_YELLOW);
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

			$inTokens = $agentUsage->inputTokens ?? 'N/A';
			$outTokens = $agentUsage->outputTokens ?? 'N/A';
			$total = $agentUsage?->getTotal() ?? 'N/A';

			$output->block("Input Tokens: $inTokens", Output::COLOR_MAGENTA);
			$output->block("Output Tokens: $outTokens", Output::COLOR_MAGENTA);
			$output->block("Total Tokens: $total", Output::COLOR_MAGENTA);

			$tokenObserver->logAgent($agent->getName(), $inTokens, $outTokens, $total);
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
