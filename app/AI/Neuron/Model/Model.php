<?php

declare(strict_types=1);

namespace App\AI\Neuron\Model;

use NeuronAI\HttpClient\HttpClientInterface;

abstract class Model
{
	private string $name = '';
	/** @var array<string,mixed> */
	private array $parameters = [];
	private bool $strictResponse = false;
	private ?HttpClientInterface $httpClient = null;

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**  @param array<string,mixed> $parameters */
	public function setParameters(array $parameters): void
	{
		$this->parameters = $parameters;
	}

	public function setStrictResponse(bool $strictResponse): void
	{
		$this->strictResponse = $strictResponse;
	}

	public function setHttpClient(?HttpClientInterface $httpClient): void
	{
		$this->httpClient = $httpClient;
	}

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
