<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron;

interface McpContextProvider
{
	/**
	 * @param string[] $excluded
	 */
	public function exclude(array $excluded): self;

	/**
	 * @return string[]
	 */
	public function resolve(): array;
}
