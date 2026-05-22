<?php

declare(strict_types=1);

namespace App\AI\Neuron\Provider;

use App\AI\Neuron\Model\Model;
use App\AI\Neuron\Model\ModelNameAwareInterface;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\OpenAILike;
use Sakoo\Framework\Core\Env\Env;

// for more information: https://gapgpt.app/platform-v2
class GapGpt extends OpenAILike implements ModelNameAwareInterface
{
	private const string BASE_URI = 'https://api.gapgpt.app/v1';

	public function __construct(string $model, array $parameters = [], bool $strict_response = false, ?HttpClientInterface $httpClient = null)
	{
		/** @var string $apiKey */
		$apiKey = Env::get('GAPGPT_API_KEY', '');
		parent::__construct(self::BASE_URI, $apiKey, $model, $parameters, $strict_response, $httpClient);
	}

	public static function withAIModelObject(Model $model): self
	{
		return new self(
			$model->getName(),
			$model->getParameters(),
			$model->getStrictResponse(),
			$model->getHttpClient(),
		);
	}

	public function modelName(): string
	{
		return $this->model;
	}
}
