<?php

declare(strict_types=1);

namespace App\AI\Neuron\Provider;

use App\AI\Neuron\Model\Model;
use App\AI\Neuron\Model\ModelNameAwareInterface;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\Providers\OpenAILike;
use Sakoo\Framework\Core\Env\Env;

// for more information: https://chat.avalai.ir/platform/home
class AvalAI extends OpenAILike implements ModelNameAwareInterface
{
	private const string BASE_URI = 'https://api.avalai.ir/v1/';

	public function __construct(string $model, array $parameters = [], bool $strict_response = false, ?HttpClientInterface $httpClient = null)
	{
		/** @var string $apiKey */
		$apiKey = Env::get('AVALAI_API_KEY', '');
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
