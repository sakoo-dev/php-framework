<?php

declare(strict_types=1);

namespace App\AI\Neuron\Provider;

use App\AI\Neuron\Guard\DetectionResult;
use NeuronAI\Exceptions\HttpException;
use NeuronAI\HttpClient\HttpClientInterface;

abstract class ModerationProvider
{
	protected string $baseUri;
	protected string $apiKey;
	protected string $modelName;
	protected string $providerName;
	protected HttpClientInterface $httpClient;

	/**
	 * @throws HttpException
	 */
	abstract public function call(string $text): DetectionResult;
}
