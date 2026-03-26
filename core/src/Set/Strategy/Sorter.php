<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Set\Strategy;

use Sakoo\Framework\Core\Set\Set;

/**
 * Strategy contract for sorting a Set.
 *
 * Implementations encapsulate a single comparison or ordering algorithm
 * (natural sort, custom comparator, locale-aware sort, etc.) and return a new
 * Set with elements in the desired order. The original Set is not mutated.
 * Callers select the appropriate Sorter at the use site, keeping ordering
 * concerns out of the Set itself.
 *
 * @template T
 */
interface Sorter
{
	/**
	 * Sorts $items according to the implementation's ordering strategy and returns
	 * a new Set with elements in the sorted order. The original Set is not modified.
	 *
	 * @param Set<T> $items
	 *
	 * @return Set<T>
	 */
	public function sort(Set $items): Set;
}
