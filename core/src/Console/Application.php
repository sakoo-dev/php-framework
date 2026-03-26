<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console;

use Sakoo\Framework\Core\Console\Commands\HelpCommand;
use Sakoo\Framework\Core\Console\Commands\NotFoundCommand;
use Sakoo\Framework\Core\Console\Commands\VersionCommand;
use Sakoo\Framework\Core\Console\Exceptions\CommandNotFoundException;

/**
 * Console application dispatcher.
 *
 * Manages a registry of Command instances and dispatches each CLI invocation to
 * the correct command based on the first positional argument. Three built-in
 * commands are always available and take precedence over registered commands:
 *
 * - VersionCommand — invoked when --version / -v is passed or the argument is 'version'.
 * - HelpCommand    — invoked when --help / -h is passed, the argument is 'help', or no
 *                    argument is provided and no default command has been set.
 * - NotFoundCommand — invoked when the requested argument does not match any registered command.
 *
 * A default command can be configured via setDefaultCommand(); it is executed when
 * the user provides no positional argument and the application has a known fallback.
 *
 * When --help / -h is detected regardless of the resolved command, the command's
 * help() method is called instead of run(), allowing every command to expose usage
 * documentation through a consistent interface.
 */
class Application
{
	/** @var Command[] */
	private array $commands = [];
	/** @var null|class-string<Command> */
	private ?string $defaultCommand = null;

	public function __construct(
		private readonly Input $input,
		private readonly Output $output,
	) {}

	/**
	 * Resolves the command that should execute for the current CLI invocation,
	 * then calls either help() (when --help / -h is present) or run(). Returns
	 * the command's exit code.
	 */
	public function run(): int
	{
		$command = $this->getShouldExecCommand();

		if ($this->input->hasOption('help') || $this->input->hasOption('h')) {
			return $command->help($this->input, $this->output);
		}

		return $command->run($this->input, $this->output);
	}

	/**
	 * Registers multiple commands at once, delegating to addCommand() for each.
	 *
	 * @param Command[] $commands
	 */
	public function addCommands(array $commands): void
	{
		foreach ($commands as $command) {
			$this->addCommand($command);
		}
	}

	/**
	 * Registers a single command in the dispatch registry, keyed by its static
	 * getName() value, and injects the running application reference into it.
	 */
	public function addCommand(Command $command): void
	{
		$command->setRunningApplication($this);
		$this->commands[$command::getName()] = $command;
	}

	/**
	 * Sets the command to execute when no positional argument is given.
	 * The command must already be registered via addCommand().
	 *
	 * @param class-string<Command> $command
	 *
	 * @throws \Throwable when the command has not been registered
	 */
	public function setDefaultCommand(string $command): void
	{
		throwUnless(isset($this->commands[$command::getName()]), new CommandNotFoundException('Command is not in your list'));
		$this->defaultCommand = $command;
	}

	/**
	 * Returns all registered commands keyed by their name.
	 *
	 * @return Command[]
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	/**
	 * Resolves which Command instance to execute for the current invocation.
	 *
	 * Priority order:
	 * 1. VersionCommand  — when --version / -v flag or 'version' argument is present.
	 * 2. HelpCommand     — when --help / -h flag, 'help' argument, or no argument and no default.
	 * 3. Default command — when no argument is given but a default has been configured.
	 * 4. Registered command by name — when the argument matches a registered command.
	 * 5. NotFoundCommand — when none of the above match.
	 */
	private function getShouldExecCommand(): Command
	{
		$arg = $this->input->getArgument(0);

		if ($this->shouldRunVersionCommand($arg)) {
			/** @var VersionCommand $command */
			$command = resolve(VersionCommand::class);
			$command->setRunningApplication($this);

			return $command;
		}

		if ($this->shouldRunHelpCommand($arg)) {
			/** @var HelpCommand $command */
			$command = resolve(HelpCommand::class);
			$command->setRunningApplication($this);

			return $command;
		}

		if (empty($arg)) {
			// @phpstan-ignore staticMethod.nonObject
			return $this->commands[$this->defaultCommand::getName()];
		}

		if (isset($this->commands[$arg])) {
			return $this->commands[$arg];
		}

		/** @var NotFoundCommand $command */
		$command = resolve(NotFoundCommand::class);
		$command->setRunningApplication($this);

		return $command;
	}

	/**
	 * Returns true when the invocation should be routed to VersionCommand.
	 */
	private function shouldRunVersionCommand(?string $arg): bool
	{
		return $this->input->hasOption('version') || VersionCommand::getName() === $arg || $this->input->hasOption('v');
	}

	/**
	 * Returns true when the invocation should be routed to HelpCommand.
	 */
	private function shouldRunHelpCommand(?string $arg): bool
	{
		return $this->input->hasOption('help') || HelpCommand::getName() === $arg || $this->input->hasOption('h') || (is_null($arg) && is_null($this->defaultCommand));
	}
}
