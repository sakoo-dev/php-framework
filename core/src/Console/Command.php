<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Console;

/**
 * Base class for all console commands.
 *
 * Subclasses must implement three abstract members:
 *
 * - getName()        — the CLI argument string used to invoke the command (e.g. 'cache:clear').
 * - getDescription() — a single-line human-readable summary shown in help output.
 * - run()            — the command's entry point; receives parsed Input and Output and
 *                      must return an integer exit code (Output::SUCCESS or an error code).
 *
 * A default help() implementation is provided: it writes the command description as a
 * yellow console block and returns Output::SUCCESS. Subclasses may override help() to
 * show more detailed usage instructions.
 *
 * The running Application is injected by Application::addCommand() so commands can
 * introspect the registry (e.g. to list all commands in HelpCommand) via getApplication().
 */
abstract class Command
{
	private Application $application;

	/**
	 * Executes the command logic. Receives the parsed CLI Input and the console Output,
	 * and must return an integer exit code.
	 */
	abstract public function run(Input $input, Output $output): int;

	/**
	 * Prints the command description as a yellow console block and returns Output::SUCCESS.
	 * Override to provide more detailed usage instructions for the command.
	 */
	public function help(Input $input, Output $output): int
	{
		$output->block(static::getDescription(), Output::COLOR_YELLOW);

		return Output::SUCCESS;
	}

	/**
	 * Returns the CLI argument name used to invoke this command (e.g. 'cache:clear').
	 * Must be unique across all registered commands in the Application.
	 */
	abstract public static function getName(): string;

	/**
	 * Returns a single-line human-readable description of what the command does.
	 * Displayed in help listings and by the default help() implementation.
	 */
	abstract public static function getDescription(): string;

	/**
	 * Injects the running Application instance so the command can access the full
	 * command registry if needed. Called automatically by Application::addCommand().
	 */
	public function setRunningApplication(Application $app): void
	{
		$this->application = $app;
	}

	/**
	 * Returns the Application instance that owns this command.
	 * Only available after setRunningApplication() has been called.
	 */
	public function getApplication(): Application
	{
		return $this->application;
	}
}
