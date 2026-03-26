<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;
use Sakoo\Framework\Core\Kernel\Exceptions\KernelIsNotStartedException;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Set\IterableInterface;
use Sakoo\Framework\Core\Set\Set;
use Sakoo\Framework\Core\Str\Str;
use Sakoo\Framework\Core\Str\Stringable;
use Sakoo\Framework\Core\VarDump\VarDump;

if (!function_exists('set')) {
	/**
	 * Creates a new type-safe Set collection from the given array.
	 *
	 * The Set infers its generic type T from the first element and rejects any
	 * subsequent element whose PHP type differs, throwing GenericMismatchException.
	 * Returns an empty Set when called with no arguments.
	 *
	 * @template T
	 *
	 * @param T[] $value
	 *
	 * @return IterableInterface<T>
	 *
	 * @throws InvalidArgumentException|Throwable
	 */
	function set(array $value = []): IterableInterface
	{
		return new Set($value);
	}
}

if (!function_exists('kernel')) {
	/**
	 * Returns the singleton Kernel instance.
	 *
	 * Provides global access to the running kernel so modules and helpers can
	 * inspect the current Mode and Environment without injecting the Kernel
	 * as a dependency.
	 *
	 * @throws KernelIsNotStartedException when called before Kernel::run() completes
	 */
	function kernel(): Kernel
	{
		return Kernel::getInstance();
	}
}

if (!function_exists('container')) {
	/**
	 * Returns the active ContainerInterface instance from the running kernel.
	 *
	 * Shorthand for kernel()->getContainer(). Intended for use in bootstrap code
	 * and framework internals; application and domain code should prefer constructor
	 * injection over calling this function directly.
	 */
	function container(): ContainerInterface
	{
		return kernel()->getContainer();
	}
}

if (!function_exists('resolve')) {
	/**
	 * Resolves a class or interface from the container and returns the result.
	 *
	 * The generic type parameter T allows call sites to retain the concrete type
	 * without an explicit cast when the return type can be inferred from $interface.
	 *
	 * @template T
	 *
	 * @param class-string<T> $interface
	 *
	 * @return T
	 */
	function resolve(string $interface)
	{
		// @phpstan-ignore return.type
		return container()->resolve($interface);
	}
}

if (!function_exists('makeInstance')) {
	/**
	 * Directly instantiates $class via the container, optionally passing $args as
	 * constructor arguments. When $args is empty the container autowires dependencies.
	 *
	 * Useful for creating transient objects that are not registered as bindings but
	 * whose dependencies should still be resolved from the container.
	 *
	 * @param mixed[] $args
	 */
	function makeInstance(string $class, array $args = []): object
	{
		return container()->new($class, $args);
	}
}

if (!function_exists('throwIf')) {
	/**
	 * Throws $exception when $condition is true. A concise guard-clause helper that
	 * reads naturally in the positive form: "throw if the condition holds.".
	 *
	 * @throws Throwable
	 */
	function throwIf(bool $condition, Throwable $exception): void
	{
		if ($condition) {
			throw $exception;
		}
	}
}

if (!function_exists('throwUnless')) {
	/**
	 * Throws $exception when $condition is false. The inverse of throwIf(); reads
	 * naturally in the negative form: "throw unless the condition holds.".
	 *
	 * @throws Throwable
	 */
	function throwUnless(bool $condition, Throwable $exception): void
	{
		throwIf(!$condition, $exception);
	}
}

if (!function_exists('logger')) {
	/**
	 * Resolves and returns the PSR-3 LoggerInterface instance from the container.
	 *
	 * Provides a global shorthand for obtaining the active logger without injecting
	 * LoggerInterface explicitly in contexts where DI is impractical (e.g. legacy
	 * scripts, closures, or quick debug statements).
	 */
	function logger(): LoggerInterface
	{
		return resolve(LoggerInterface::class);
	}
}

if (!function_exists('str')) {
	/**
	 * Wraps $value in a Str instance, giving access to the full fluent string
	 * manipulation API.
	 *
	 * The returned object implements both Stringable and PHP's native \Stringable
	 * so it can be used wherever a plain string is expected.
	 */
	function str(string $value): Stringable
	{
		return new Str($value);
	}
}

if (!function_exists('__')) {
	/**
	 * Translation stub that returns its input unchanged.
	 *
	 * Acts as a placeholder for the localisation system. Wrapping user-facing
	 * strings in __() marks them as translatable and ensures they can be swapped
	 * for a real translation function when i18n support is added, without requiring
	 * changes at every call site.
	 */
	function __(string $value): string
	{
		return $value;
	}
}

if (!function_exists('dump')) {
	/**
	 * Renders a human-readable debug representation of each value in $values through
	 * the active VarDump::dump() implementation and returns normally.
	 *
	 * The concrete output format (HTML, CLI, etc.) is determined by the Dumper
	 * implementation bound in the container.
	 */
	function dump(mixed ...$values): void
	{
		VarDump::dump(...$values);
	}
}

if (!function_exists('dd')) {
	/**
	 * Renders a human-readable debug representation of each value in $values through
	 * VarDump::dieDump() and then terminates the process immediately.
	 *
	 * Equivalent to calling dump() followed by exit. Declared as never-returning so
	 * PHPStan and static analysers correctly model the control-flow termination.
	 */
	function dd(mixed ...$values): never
	{
		VarDump::dieDump(...$values);
	}
}
