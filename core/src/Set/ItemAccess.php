<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Set;

/**
 * Provides positional accessor shortcuts for the first ten elements of a Set.
 *
 * Mixed into Set, these methods offer semantically named access (first(), second(),
 * …, tenth()) as readable alternatives to get(0), get(1), etc. They are purely
 * convenience wrappers and delegate directly to get() with a fixed integer index.
 *
 * All methods return mixed because the Set's generic type T is not expressible in
 * PHP's native type system at the trait level.
 */
trait ItemAccess
{
	/** Returns the element at index 0, or null when the Set has fewer than one element. */
	public function first(): mixed
	{
		return $this->get(0);
	}

	/** Returns the element at index 1, or null when the Set has fewer than two elements. */
	public function second(): mixed
	{
		return $this->get(1);
	}

	/** Returns the element at index 2, or null when the Set has fewer than three elements. */
	public function third(): mixed
	{
		return $this->get(2);
	}

	/** Returns the element at index 3, or null when the Set has fewer than four elements. */
	public function fourth(): mixed
	{
		return $this->get(3);
	}

	/** Returns the element at index 4, or null when the Set has fewer than five elements. */
	public function fifth(): mixed
	{
		return $this->get(4);
	}

	/** Returns the element at index 5, or null when the Set has fewer than six elements. */
	public function sixth(): mixed
	{
		return $this->get(5);
	}

	/** Returns the element at index 6, or null when the Set has fewer than seven elements. */
	public function seventh(): mixed
	{
		return $this->get(6);
	}

	/** Returns the element at index 7, or null when the Set has fewer than eight elements. */
	public function eighth(): mixed
	{
		return $this->get(7);
	}

	/** Returns the element at index 8, or null when the Set has fewer than nine elements. */
	public function ninth(): mixed
	{
		return $this->get(8);
	}

	/** Returns the element at index 9, or null when the Set has fewer than ten elements. */
	public function tenth(): mixed
	{
		return $this->get(9);
	}
}
