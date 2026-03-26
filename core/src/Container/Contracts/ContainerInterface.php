<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container\Contracts;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Extends the PSR-11 ContainerInterface with Sakoo-specific resolution, binding,
 * and cache capabilities.
 *
 * In addition to the standard has() / get() PSR contract, this interface exposes
 * the full lifecycle of dependency registration:
 *
 * - bind() registers a transient factory: a fresh object is created on every resolve.
 * - singleton() registers a shared factory: the object is created once and reused.
 * - resolve() performs resolution regardless of whether the identifier was registered,
 *   falling back to autowiring when no explicit binding exists.
 * - new() bypasses the registry and directly instantiates a class, optionally
 *   with explicit constructor arguments, or via autowiring when none are provided.
 * - clear() resets the entire container state, including cached instances.
 *
 * Implementations must also satisfy the ShouldCache contract, enabling the binding
 * map to be persisted and restored across requests for performance.
 */
interface ContainerInterface extends PsrContainerInterface, ShouldCache
{
	/**
	 * Resolves $interface to a concrete object. When the identifier has a singleton
	 * or binding registered, that factory is used. Otherwise, the container attempts
	 * to instantiate the class directly via autowiring.
	 *
	 * @throws \Throwable
	 */
	public function resolve(string $interface): object;

	/**
	 * Directly instantiates $class, injecting $params as constructor arguments. When
	 * $params is empty and the constructor has typed parameters, the container will
	 * autowire dependencies automatically.
	 *
	 * @param mixed[] $params
	 *
	 * @throws \Throwable
	 */
	public function new(string $class, array $params = []): object;

	/**
	 * Registers $factory as a transient binding for $interface. A new instance is
	 * produced each time $interface is resolved.
	 *
	 * @throws \Throwable
	 */
	public function bind(string $interface, callable|object|string $factory): void;

	/**
	 * Registers $factory as a singleton binding for $interface. The instance is
	 * created on first resolution and reused on all subsequent resolutions.
	 *
	 * @throws \Throwable
	 */
	public function singleton(string $interface, callable|object|string $factory): void;

	/**
	 * Removes all bindings, singleton registrations, and cached instances from the
	 * container, returning it to a pristine state.
	 */
	public function clear(): void;
}
