<?php

declare(strict_types=1);

namespace App\AI\Neuron\Model\Anthropic;

use App\AI\Neuron\Model\Model;
use NeuronAI\HttpClient\GuzzleHttpClient;
use NeuronAI\HttpClient\HttpClientInterface;

class Claude extends Model
{
	public const SONNET_4_6 = 'claude-sonnet-4-6';
	public const SONNET_4_7 = 'claude-sonnet-4-7';

	public const OPUS_4_6 = 'claude-opus-4-6';
	public const OPUS_4_7 = 'claude-opus-4-7';

	public const HAIKU_3_5 = 'claude-3-5-haiku-20241022';

	public function __construct(
		string $modelName,
		bool $extendedThinking = false,
		?HttpClientInterface $httpClient = null,
	) {
		$this->name = $modelName;
		$this->parameters = [];
		$params = [];

		if ($extendedThinking) {
			$this->parameters = ['thinking' => ['type' => 'enabled', 'budget_tokens' => 10000]];
			$params = ['anthropic-beta' => 'interleaved-thinking-2025-05-14'];
		}

		$this->httpClient = ($httpClient?->withHeaders($params) ?? new GuzzleHttpClient($params));
	}
}
