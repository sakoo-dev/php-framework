<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\AI\Agent\ChatBotAgent;
use App\AI\Agent\DataAnalystAgent;
use App\AI\Agent\DeveloperAgent;
use App\AI\Agent\ProductManagerAgent;
use App\AI\Agent\PsychologistAgent;
use App\Assist\Commands\Formatter\CLIAgentStreamFormatter;
use Sakoo\AI\Agent;
use Sakoo\AI\Agent\Runner\AgentRunnerFactory;
use Sakoo\AI\Agent\Runner\TurnStatus;
use Sakoo\AI\Neuron\Session\ChatSession;
use Sakoo\AI\Neuron\Session\ChatSessionRepository;
use Sakoo\AI\Neuron\Session\SessionId;
use Sakoo\AI\WorkerAgent;
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
		PsychologistAgent::class,
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

		$agent = $selectedClass::make();
		$session = $this->resolveSession($agent->getName(), $input, $output);
		$factory = resolve(AgentRunnerFactory::class);
		$runner = $factory->make($agent, $session);
		$formatter = new CLIAgentStreamFormatter($output);

		$output->block([
			"\t\t=======================",
			"\t\t=========",
			' =======================',
		], Output::COLOR_WHITE);

		$output->block('Welcome to Sakoo ' . strtoupper($agent->getName()) . ' Agent!', Output::COLOR_RED);
		$output->block('Session: ' . $session->sessionId->value, Output::COLOR_MAGENTA);

		$pendingPrompt = null;

		// @phpstan-ignore while.alwaysTrue
		while (true) {
			if (null === $pendingPrompt) {
				$output->block('Enter Your Prompt:', Output::COLOR_YELLOW);
			}

			$prompt = $pendingPrompt ?? $input->getUserInput();
			$pendingPrompt = null;

			$output->block('Processing...', Output::COLOR_CYAN);

			$result = $runner->turn($prompt);

			if ($result->needsRetry()) {
				$pendingPrompt = $result->retryPrompt;

				if ($result->orphanedToolUse) {
					$output->block('Orphaned tool_use detected — pruned history, retrying...', Output::COLOR_RED);
				} elseif ($result->mcpError) {
					$output->block('MCP Tool Error. Retrying...', Output::COLOR_RED);
				} else {
					$output->block('Chat History Error Fixing...', Output::COLOR_RED);
				}

				continue;
			}

			if (TurnStatus::GuardViolation === $result->status) {
				$output->newLine();
				$output->block(
					'⚠ ' . $result->guardViolation?->classification->value . ': ' . $result->guardViolation?->reason,
					Output::COLOR_RED,
				);

				continue;
			}

			if (TurnStatus::RecoverableError === $result->status || TurnStatus::FatalError === $result->status) {
				$label = '' !== $result->errorLabel ? $result->errorLabel : get_class($result->error ?? new \RuntimeException());
				$message = $result->error?->getMessage() ?? '';
				$output->block($label . ('' !== $message ? ': ' . $message : ''), Output::COLOR_RED);

				continue;
			}

			foreach ($result->chunks as [$chunk, $previousChunk]) {
				$formatter->format($chunk, $previousChunk);
			}

			$output->newLine();
			$output->block('Powered By: ' . $result->providerName . ' | ' . $result->modelName, Output::COLOR_MAGENTA);
			$output->block('Input Tokens: ' . $result->inputTokens . ' (' . $result->contextUsagePercent() . '% of context)', Output::COLOR_MAGENTA);
			$output->block('Output Tokens: ' . ($result->outputTokens ?: 'N/A'), Output::COLOR_MAGENTA);
			$output->block('Total Tokens: ' . ($result->totalTokens ?: 'N/A'), Output::COLOR_MAGENTA);

			if ($result->streamState?->hasOptimization()) {
				$output->block(
					'⚡ Optimization: Saved ' . $result->streamState->tokensSaved() . ' tokens (' . $result->streamState->savingsPercent() . '% reduction)',
					Output::COLOR_CYAN,
				);
			}
		}

		// @phpstan-ignore deadCode.unreachable
		return Output::SUCCESS;
	}

	private function resolveSession(string $agentName, Input $input, Output $output): ChatSession
	{
		$existing = resolve(ChatSessionRepository::class)->findByAgent($agentName);

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
}
