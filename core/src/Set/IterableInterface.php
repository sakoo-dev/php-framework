<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Set;

use Sakoo\Framework\Core\Set\Strategy\Searcher;
use Sakoo\Framework\Core\Set\Strategy\Sorter;

/**
 * Contract for a generic, type-safe iterable collection.
 *
 * Extends both IteratorAggregate (for foreach support) and Countable (for count()).
 * All mutating methods return the same interface type so call sites can chain
 * operations fluently without knowing the concrete implementation.
 *
 * The generic parameter T represents the element type enforced by the collection.
 * Implementations must reject elements whose type differs from T and signal the
 * violation via an appropriate exception (e.g. GenericMismatchException).
 *
 * Sorting and searching are delegated to pluggable Strategy objects (Sorter<T> and
 * Searcher<T>) so the interface remains algorithm-agnostic.
 *
 * @template T
 *
 * @extends \IteratorAggregate<int|string, T>
 */
interface IterableInterface extends \IteratorAggregate, \Countable
{
	/**
	 * Returns true when an element with the given int or string key exists in the
	 * collection, false otherwise.
	 */
	public function exists(int|string $name): bool;

	/**
	 * Returns the number of elements currently in the collection.
	 */
	public function count(): int;

	/**
	 * Iterates over every element, passing each value and key to $callback.
	 * The callback's return value is ignored.
	 */
	public function each(callable $callback): void;

	/**
	 * Applies $callback to every element and returns a new collection containing
	 * the transformed values. The original collection is not modified.
	 *
	 * @template U
	 *
	 * @param callable(T): U $callback
	 *
	 * @return IterableInterface<U>
	 */
	public function map(callable $callback): self;

	/**
	 * Extracts a column of values identified by a dot-notation $key from each
	 * element and returns a new collection of those values.
	 *
	 * @return IterableInterface<T>
	 */
	public function pluck(string $key): self;

	/**
	 * Adds an element to the collection. When only $key is provided (and $value is
	 * null), $key is treated as the value and appended with a sequential index.
	 * When both arguments are given, $key is used as the explicit array key.
	 *
	 * @return IterableInterface<T>
	 */
	public function add(mixed $key, mixed $value = null): self;

	/**
	 * Removes the element at $key and returns the same collection. Integer keys are
	 * treated as positional indices; string keys are used as associative keys.
	 *
	 * @return IterableInterface<T>
	 */
	public function remove(int|string $key): self;

	/**
	 * Returns the element at $key, or $default when the key does not exist.
	 * Integer keys are resolved positionally.
	 *
	 * @return null|T
	 */
	public function get(int|string $key, mixed $default = null): mixed;

	/**
	 * Delegates sorting to $sorter and returns a new collection with elements in
	 * the order determined by the strategy.
	 *
	 * @param Sorter<T> $sorter
	 *
	 * @return IterableInterface<T>
	 */
	public function sort(Sorter $sorter): self;

	/**
	 * Delegates searching to $searcher and returns a new collection containing only
	 * elements that match $needle according to the strategy.
	 *
	 * @param Searcher<T> $searcher
	 *
	 * @return IterableInterface<T>
	 */
	public function search(mixed $needle, Searcher $searcher): self;

	/**
	 * Returns a new collection containing only the elements for which $callback
	 * returns true.
	 *
	 * @return IterableInterface<T>
	 */
	public function filter(callable $callback): self;

	/**
	 * Returns the contents of the collection as a plain PHP array.
	 *
	 * @return array<mixed>
	 */
	public function toArray(): array;
}
