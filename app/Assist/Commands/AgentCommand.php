<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\AI\Agent\Agent;
use App\AI\Agent\ChatBotAgent;
use App\AI\Agent\Consult\ArchitectAgent;
use App\AI\Agent\Consult\WorkerAgent;
use App\AI\Agent\DataAnalystAgent;
use App\AI\Agent\DeveloperAgent;
use App\AI\Agent\ProductManagerAgent;
use App\AI\Mcp\McpContextProvider;
use App\AI\Mcp\McpElements;
use App\AI\Metric\AgentMetricObserver;
use App\AI\Metric\MetricSource;
use App\AI\Metric\MetricStorageInterface;
use App\AI\Metric\QualityEvaluatorInterface;
use App\AI\Neuron\Session\ChatSession;
use App\AI\Neuron\Session\ChatSessionRepository;
use App\AI\Neuron\Session\SessionId;
use App\Assist\Commands\Formatter\CliAgentStreamFormatter;
use NeuronAI\Chat\Messages\Stream\Chunks\StreamChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ChatHistoryException;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Observability\LogObserver;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Workflow\Interrupt\WorkflowInterrupt;
use Psr\Log\LoggerInterface;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;

class AgentCommand extends Command
{
	private const string START_FRESH_SESSION = 'Start fresh session';
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
		/** @var class-string<Agent> $selectedClass */
		$selectedClass = $input->radio($this->agents, 'Select an Agent to Talk with:');

		/** @var Agent $agent */
		$agent = $this->buildAgent($selectedClass);

		$session = $this->resolveSession($agent->getName(), $input, $output);
		$agent->withSession($session);

		$mcpContextProvider = new McpContextProvider(McpElements::class);
		$mcpContextProvider = $mcpContextProvider->exclude($agent->getExcludedContexts());
		$agent->withMcpContext($mcpContextProvider);

		/** @var AIProviderInterface $provider */
		$provider = resolve(AIProviderInterface::class);

		/** @var LoggerInterface $logger */
		// @phpstan-ignore argument.type
		$logger = resolve('logger.ai');
		$agent->observe(new LogObserver($logger));

		$agent->observe(new AgentMetricObserver(
			storage: resolve(MetricStorageInterface::class),
			qualityEvaluator: resolve(QualityEvaluatorInterface::class),
			sessionId: $session->sessionId,
			agentName: $agent->getName(),
			modelName: $agent->getModelName(),
			providerName: $provider::class,
			source: MetricSource::Live,
		));

		$output->block([
			"\t\t=======================",
			"\t\t=========",
			' =======================',
		], Output::COLOR_WHITE);

		$output->block('Welcome to Sakoo ' . strtoupper($agent->getName()) . ' Agent!', Output::COLOR_RED);
		$output->block('Session: ' . $session->sessionId->value, Output::COLOR_MAGENTA);

		$formatter = new CliAgentStreamFormatter($output);

		// @phpstan-ignore while.alwaysTrue
		while (true) {
			$output->block('Enter Your Prompt:', Output::COLOR_YELLOW);
			$prompt = $input->getUserInput();

			$output->block('Processing...', Output::COLOR_CYAN);

			try {
				$agentStream = $agent->stream(new UserMessage($prompt));

				/** @var ?StreamChunk $previousChunk */
				$previousChunk = null;

				/** @var StreamChunk $chunk */
				foreach ($agentStream->events() as $chunk) {
					$formatter->format($chunk, $previousChunk);
					$previousChunk = $chunk;
				}
			} catch (ChatHistoryException $e) {
				if (str_contains($e->getMessage(), 'Invalid message sequence at position')) {
					// @phpstan-ignore method.notFound
					$agent->getChatHistory()->removeLastLog();
					$output->block('Chat History Error Fixing ...', Output::COLOR_RED);
				} else {
					$output->block('Chat History Exception', Output::COLOR_RED);
				}

				continue;
			} catch (WorkflowInterrupt $e) {
				$output->block('Workflow Interrupt: ' . $e->getMessage(), Output::COLOR_RED);

				continue;
			} catch (WorkflowException $e) {
				$output->block('Workflow Exception: ' . $e->getMessage(), Output::COLOR_RED);

				continue;
			} catch (\Throwable $e) {
				$output->block('Unknown Exception: ' . $e->getMessage(), Output::COLOR_RED);

				continue;
			}

			$output->newLine();
			/** @phpstan-ignore variable.undefined */
			$agentUsage = $agentStream->getMessage()->getUsage();
			$output->block('Input Tokens: ' . ($agentUsage->inputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Output Tokens: ' . ($agentUsage->outputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Total Tokens: ' . ($agentUsage?->getTotal() ?? 'N/A'), Output::COLOR_MAGENTA);
		}

		// @phpstan-ignore deadCode.unreachable
		return Output::SUCCESS;
	}

	/**
	 * Asks whether to resume an existing session or start fresh. Existing sessions
	 * are discovered by ChatSessionRepository and presented via the console radio.
	 */
	private function resolveSession(string $agentName, Input $input, Output $output): ChatSession
	{
		$existing = (new ChatSessionRepository())->findByAgent($agentName);

		if ([] === $existing) {
			return $this->startNewSession($output, $agentName);
		}

		$choices = [self::START_FRESH_SESSION];

		foreach ($existing as $s) {
			$choices[] = 'Resume: ' . $s->sessionId->value;
		}

		/** @var string $choice */
		$choice = $input->radio($choices, 'Chat history found. Resume a session or start fresh?');

		if (self::START_FRESH_SESSION === $choice) {
			return $this->startNewSession($output, $agentName);
		}

		$sessionId = SessionId::fromString(str_replace('Resume: ', '', $choice));
		$session = new ChatSession($sessionId, $agentName);
		$output->block('Resuming session: ' . $sessionId->value, Output::COLOR_CYAN);

		return $session;
	}

	private function startNewSession(Output $output, string $agentName): ChatSession
	{
		$output->block('Starting new session.', Output::COLOR_CYAN);

		return new ChatSession(SessionId::generate(), $agentName);
	}

	/**
	 * @param class-string<Agent> $class
	 */
	private function buildAgent(string $class): Agent
	{
		if (WorkerAgent::class === $class) {
			return new WorkerAgent(ArchitectAgent::make());
		}

		return $class::make();
	}
}
