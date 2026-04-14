<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron;

use NeuronAI\StructuredOutput\SchemaProperty;

final class ArchitectDirective
{
	#[SchemaProperty(description: 'Approved | Revised | Blocked', required: true)]
	public string $decision;

	#[SchemaProperty(description: 'Concrete guidance the worker must follow to continue.', required: true)]
	public string $guidance;

	#[SchemaProperty(description: 'Estimated complexity: low | medium | high', required: true)]
	public string $complexity;

	#[SchemaProperty(description: 'Reason for Blocked decision; empty otherwise.')]
	public string $blockedReason = '';
}
