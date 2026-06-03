<?php

declare(strict_types=1);

namespace App\AI\Neuron\Provider;

use App\AI\Neuron\Guard\ContentClassification;
use App\AI\Neuron\Guard\DetectionResult;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpMethod;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\HttpClient\HttpResponse;

class OpenAIModeration extends ModerationProvider
{
	public const MODERATION_LATEST = 'text-moderation-latest';
	public const MODERATION_007 = 'text-moderation-007';
	public const MODERATION_OMNI_LATEST = 'omni-moderation-latest';

	public function __construct(string $baseUri, string $apiKey, string $modelName, HttpClientInterface $httpClient)
	{
		$this->baseUri = $baseUri;
		$this->apiKey = $apiKey;
		$this->modelName = $modelName;
		$this->httpClient = $httpClient;
		$this->providerName = 'OpenAI';
	}

	public function call(string $text): DetectionResult
	{
		$request = new HttpRequest(
			method: HttpMethod::POST,
			uri: $this->baseUri . '/moderations',
			headers: [
				// @phpstan-ignore cast.string
				'Authorization' => 'Bearer ' . $this->apiKey,
				'Content-Type' => 'application/json',
			],
			body: ['model' => $this->modelName, 'input' => $text],
		);

		$response = $this->httpClient->request($request);

		return $this->parseResponse($response, $text);
	}

	private function parseResponse(HttpResponse $response, string $text): DetectionResult
	{
		$responseBody = json_decode($response->body, true);

		if (!is_array($responseBody)) {
			return DetectionResult::clean($text);
		}

		/** @var array<string, mixed> $firstResult */
		$firstResult = is_array($responseBody['results'] ?? null) ? ($responseBody['results'][0] ?? []) : [];

		if (!($firstResult['flagged'] ?? false)) {
			return DetectionResult::clean($text);
		}

		/** @var array<string, bool> $categories */
		$categories = (array) ($firstResult['categories'] ?? []);
		$flagged = array_keys(array_filter($categories));

		/** @var list<array{ContentClassification, string}> $classified */
		$classified = [];

		foreach ($flagged as $category) {
			$classification = $this->classifyCategory($category);

			if (null !== $classification) {
				$classified[] = [$classification, 'OpenAI moderation flagged: ' . $category];
			}
		}

		if (!$classified) {
			return DetectionResult::clean($text);
		}

		[$firstClassification, $firstReason] = array_shift($classified);
		$result = DetectionResult::flag($firstClassification, $firstReason, $text);

		foreach ($classified as [$classification, $reason]) {
			$result = $result->withReason($classification, $reason);
		}

		return $result;
	}

	private function classifyCategory(string $category): ?ContentClassification
	{
		$map = [
			'hate' => ContentClassification::Restricted,
			'hate/threatening' => ContentClassification::Restricted,
			'harassment' => ContentClassification::Restricted,
			'harassment/threatening' => ContentClassification::Restricted,
			'self-harm' => ContentClassification::Restricted,
			'self-harm/intent' => ContentClassification::Restricted,
			'self-harm/instructions' => ContentClassification::Restricted,
			'sexual/minors' => ContentClassification::Restricted,
			'violence/graphic' => ContentClassification::Restricted,
			'sexual' => ContentClassification::Confidential,
			'violence' => ContentClassification::Internal,
		];

		return $map[$category] ?? null;
	}
}
