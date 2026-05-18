<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Model;

use NeuronAI\HttpClient\HttpClientInterface;

abstract class Model
{
	protected string $name = '';
	/** @var array<string,mixed> */
	protected array $parameters = [];
	protected bool $strictResponse = false;
	protected ?HttpClientInterface $httpClient = null;

	public function getName(): string
	{
		return $this->name;
	}

	/** @return array<string,mixed> */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	public function getStrictResponse(): bool
	{
		return $this->strictResponse;
	}

	public function getHttpClient(): ?HttpClientInterface
	{
		return $this->httpClient;
	}
}
