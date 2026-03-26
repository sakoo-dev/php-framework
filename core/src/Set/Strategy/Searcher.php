<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Set\Strategy;

use Sakoo\Framework\Core\Set\Set;

/**
 * Strategy contract for searching within a Set.
 *
 * Implementations encapsulate a single search algorithm (linear scan, binary
 * search, index lookup, etc.) and return a new Set containing only the elements
 * that match the given needle. This keeps the Set class free of search-specific
 * logic and allows algorithms to be swapped or combined at call sites without
 * changing the collection itself.
 *
 * @template T
 */
interface Searcher
{
	/**
	 * Searches $items for elements matching $needle and returns a new Set containing
	 * only the matched elements. The original Set is not modified.
	 *
	 * @param Set<T> $items
	 *
	 * @return Set<T>
	 */
	public function search(Set $items, mixed $needle): Set;
}
