<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Provider;

use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
use Sakoo\Framework\Core\Env\Env;

// for more information: https://gapgpt.app/platform-v2
class GapGptEmbedding extends OpenAIEmbeddingsProvider
{
	protected string $baseUri = 'https://api.gapgpt.app/v1';

	public function __construct(string $model, ?int $dimensions = 1024)
	{
		/** @var string $apiKey */
		$apiKey = Env::get('GAPGPT_API_KEY', '');
		parent::__construct($apiKey, $model, $dimensions);
	}
}
