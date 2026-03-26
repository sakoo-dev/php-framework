<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Set;

use Sakoo\Framework\Core\Assert\Assert;
use Sakoo\Framework\Core\Set\Exceptions\GenericMismatchException;
use Sakoo\Framework\Core\Set\Strategy\Searcher;
use Sakoo\Framework\Core\Set\Strategy\Sorter;

/**
 * Type-safe generic collection.
 *
 * Set<T> infers the element type T from the first item inserted and rejects any
 * subsequent item whose PHP gettype() differs from that inferred type, throwing
 * GenericMismatchException. This makes the collection behave similarly to a
 * typed generic in languages with native generics, preventing silent type coercion
 * that would be possible with a plain PHP array.
 *
 * Elements can be stored with either an explicit string/int key (associative) or
 * appended without a key (sequential). Integer keys passed to get() and remove()
 * are always treated as positional indices — not as literal array keys — so
 * get(0) reliably returns the first element even in associative sets.
 *
 * The ItemAccess trait adds named positional accessors (first(), second(), …,
 * tenth()) for the most commonly accessed positions.
 *
 * Sorting and searching are delegated to pluggable Sorter<T> and Searcher<T>
 * strategy objects injected at the call site, keeping the Set algorithm-agnostic.
 *
 * Magic property access (__get / __set) maps property names to associative keys,
 * enabling object-style access on associative sets.
 *
 * @template T
 *
 * @implements IterableInterface<T>
 */
class Set implements IterableInterface
{
	private ?string $genericType = null;

	use ItemAccess;

	/**
	 * Constructs a Set from $items, inferring T from the first element and
	 * validating all subsequent elements against it.
	 *
	 * @param array<int|string,T> $items
	 *
	 * @implements \IteratorAggregate<int|string, T>
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function __construct(private array $items = [])
	{
		if (!empty($items)) {
			$this->detectGeneric(reset($items));

			foreach ($items as $element) {
				$this->typeMismatchChecking($element);
			}
		}
	}

	/**
	 * Returns the element stored under the associative key $name, or null when it
	 * does not exist. Delegates to get() so type validation applies to any default.
	 *
	 * @return null|T
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function __get(string $name): mixed
	{
		return $this->get($name);
	}

	/**
	 * Stores $value under the associative key $name without type validation.
	 * Prefer add() for validated insertion.
	 *
	 * @param T $value
	 */
	public function __set(string $name, mixed $value): void
	{
		$this->items[$name] = $value;
	}

	/**
	 * Returns true when an element with the given int or string $name key exists.
	 */
	public function exists(int|string $name): bool
	{
		return isset($this->items[$name]);
	}

	/**
	 * Returns the number of elements in the collection.
	 */
	public function count(): int
	{
		return count($this->items);
	}

	/**
	 * Passes each element (value, key) to $callback. Return values are discarded.
	 */
	public function each(callable $callback): void
	{
		array_walk($this->items, $callback);
	}

	/**
	 * Returns a new Set whose elements are the results of applying $callback to
	 * each element of this Set. The inferred generic type of the new Set is
	 * determined by the callback's return values.
	 *
	 * @template U
	 *
	 * @param callable(T): U $callback
	 *
	 * @return Set<U>
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function map(callable $callback): self
	{
		return new self(array_map($callback, $this->items));
	}

	/**
	 * Extracts nested values using a dot-notation $key (e.g. 'address.city') from
	 * each element via successive array_column calls, and returns them as a new Set.
	 *
	 * @return Set<T>
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function pluck(string $key): self
	{
		$nestedKeys = explode('.', $key);

		/** @var T[] $result */
		$result = $this->items;

		foreach ($nestedKeys as $column) {
			/** @var T[] $result */
			$result = array_column($result, $column);
		}

		return new self($result);
	}

	/**
	 * Adds an element to the collection with type validation.
	 *
	 * When only $key is provided (and $value is null), $key is treated as the
	 * value and appended sequentially. When both $key and $value are given, $value
	 * is stored under $key (which must be int or string).
	 *
	 * @return Set<T>
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function add(mixed $key, mixed $value = null): self
	{
		if (is_null($value)) {
			$this->detectGeneric($key);
			$this->typeMismatchChecking($key);
			$this->items[] = $key;

			return $this;
		}

		$this->detectGeneric($value);
		$this->typeMismatchChecking($value);

		Assert::true(is_int($key) || is_string($key), 'Provided Key is not integer or string.');
		// @phpstan-ignore offsetAccess.invalidOffset
		$this->items[$key] = $value;

		return $this;
	}

	/**
	 * Removes the element at $key from the collection and returns the same instance.
	 * Integer $key is treated as a positional index; string $key is an associative key.
	 * Does nothing when the key does not exist.
	 *
	 * @return Set<T>
	 */
	public function remove(int|string $key): self
	{
		if (is_int($key)) {
			unset($this->items[array_keys($this->items)[$key]]);

			return $this;
		}

		if ($this->exists($key)) {
			unset($this->items[$key]);
		}

		return $this;
	}

	/**
	 * Returns the element at $key, or $default when the key does not exist.
	 *
	 * Integer $key is treated as a positional index (via array_slice), allowing
	 * consistent positional access even in associative sets. When $default is
	 * provided it must satisfy the generic type constraint.
	 *
	 * @return null|T
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function get(int|string $key, mixed $default = null): mixed
	{
		if (!is_null($default)) {
			$this->typeMismatchChecking($default);
		}

		if (is_int($key)) {
			$indexValue = current(array_slice($this->items, $key, 1));

			return $indexValue ?: null;
		}

		return $this->items[$key] ?? $default;
	}

	/**
	 * Returns all elements as a plain PHP array, preserving keys.
	 *
	 * @return array<T>
	 */
	public function toArray(): array
	{
		return $this->items;
	}

	/**
	 * Returns an ArrayIterator over the internal items array, enabling foreach
	 * iteration and satisfying the IteratorAggregate contract.
	 *
	 * @return \ArrayIterator<int|string, T>
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	/**
	 * Throws GenericMismatchException when $value's PHP type does not match the
	 * inferred generic type T of this Set.
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	private function typeMismatchChecking(mixed $value): void
	{
		throwIf($this->genericType !== gettype($value), new GenericMismatchException());
	}

	/**
	 * Records the PHP type of $value as the Set's generic type T on the first call.
	 * Subsequent calls are no-ops because the type is locked after the first element.
	 */
	private function detectGeneric(mixed $value): void
	{
		if (is_null($this->genericType)) {
			$this->genericType = gettype($value);
		}
	}

	/**
	 * Delegates sorting to $sorter and returns a new Set with elements reordered
	 * according to the strategy's algorithm.
	 *
	 * @param Sorter<T> $sorter
	 *
	 * @return Set<T>
	 */
	public function sort(Sorter $sorter): self
	{
		return $sorter->sort($this);
	}

	/**
	 * Delegates searching to $searcher and returns a new Set containing only the
	 * elements that match $needle according to the strategy.
	 *
	 * @param Searcher<T> $searcher
	 *
	 * @return Set<T>
	 */
	public function search(mixed $needle, Searcher $searcher): self
	{
		return $searcher->search($this, $needle);
	}

	/**
	 * Returns a new Set containing only the elements for which $callback returns
	 * true. The inferred generic type is preserved in the new Set.
	 *
	 * @return Set<T>
	 *
	 * @throws GenericMismatchException|\Throwable
	 */
	public function filter(callable $callback): self
	{
		return new self(array_filter($this->items, $callback));
	}
}
