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
use App\Assist\AI\Metric\AgentMetricObserver;
use App\Assist\AI\Metric\MetricSource;
use App\Assist\AI\Metric\MetricStorageInterface;
use App\Assist\AI\Metric\QualityEvaluatorInterface;
use App\Assist\AI\Neuron\Session\ChatSession;
use App\Assist\AI\Neuron\Session\ChatSessionRepository;
use App\Assist\AI\Neuron\Session\SessionId;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ChatHistoryException;
use NeuronAI\Observability\LogObserver;
use NeuronAI\Providers\AIProviderInterface;
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
		/** @var class-string<BaseAgent> $selectedClass */
		$selectedClass = $input->radio($this->agents, 'Select an Agent to Talk with:');

		/** @var BaseAgent $agent */
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
			modelName: $this->resolveModelName($provider),
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

			$output->block('Input Tokens: ' . ($agentUsage->inputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Output Tokens: ' . ($agentUsage->outputTokens ?? 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Total Tokens: ' . ($agentUsage?->getTotal() ?? 'N/A'), Output::COLOR_MAGENTA);
		}

		// @phpstan-ignore deadCode.unreachable
		return Output::SUCCESS;
	}

	/**
	 * Asks whether to resume an existing session or start fresh, then returns the
	 * resolved ChatSession. Existing sessions are discovered by ChatSessionRepository
	 * and presented as a radio-button choice via the console input.
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

	/**
	 * Reads the protected $model property from the provider via reflection.
	 *
	 * All NeuronAI providers (OpenAI, OpenAILike, Anthropic, Ollama, …) declare
	 * a protected string $model constructor-promoted property. Reflection gives
	 * us the actual runtime value without requiring a getModel() contract on the
	 * interface, keeping this decoupled from vendor internals.
	 */
	private function resolveModelName(AIProviderInterface $provider): string
	{
		$reflection = new \ReflectionObject($provider);

		if ($reflection->hasProperty('model')) {
			$property = $reflection->getProperty('model');
			$property->setAccessible(true);
			$value = $property->getValue($provider);

			if (is_string($value) && '' !== $value) {
				return $value;
			}
		}

		return $provider::class;
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

	private function startNewSession(Output $output, string $agentName): ChatSession
	{
		$output->block('Starting new session.', Output::COLOR_CYAN);

		return new ChatSession(SessionId::generate(), $agentName);
	}
}
