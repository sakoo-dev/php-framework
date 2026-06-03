<?php

declare(strict_types=1);

namespace App\AI\Neuron\Tool;

use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\PostProcessor\AdaptiveThresholdPostProcessor;
use NeuronAI\RAG\PostProcessor\LocalAIRerankerPostProcessor;
use NeuronAI\RAG\PreProcessor\QueryTransformationPreProcessor;
use NeuronAI\RAG\PreProcessor\QueryTransformationType;
use NeuronAI\RAG\RAG;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

class RetrievalTool extends Tool
{
	public const string NAME = 'retrieval';

	public function __construct(protected RAG $rag)
	{
		parent::__construct(
			name: self::NAME,
			description: 'Retrieves data using RAG in a Vector Database',
			properties: [
				ToolProperty::make(
					name: 'query',
					type: PropertyType::STRING,
					description: 'The search query to retrieve relevant documents',
					required: true,
				),
			],
		);
	}

	public function execute(): void
	{
		// @phpstan-ignore argument.type
		$question = new UserMessage($this->getInput('query'));
		$question = $this->getQueryProcessor()->process($question);
		$docs = $this->rag->resolveRetrieval()->retrieve($question);
		$docs = $this->removeDuplicatesByContentHash($docs);

		/**
		 * @var LocalAIRerankerPostProcessor $reranker
		 *
		 * @phpstan-ignore argument.type
		 */
		$reranker = resolve('ai.reranker');

		$docs = $reranker->process($question, $docs);
		$docs = $this->getThresholdPostProcessor()->process($question, $docs);
		$content = implode("\n\n---\n\n", array_map(fn (Document $doc): string => $doc->getContent(), $docs));
		$this->setResult($content);
	}

	/**
	 * Remove duplicate documents based on content hash.
	 *
	 * @param Document[] $documents
	 *
	 * @return Document[]
	 */
	private function removeDuplicatesByContentHash(array $documents): array
	{
		$docs = [];

		foreach ($documents as $document) {
			$hash = md5($document->getContent());
			$docs[$hash] = $document;
		}

		return array_values($docs);
	}

	private function getQueryProcessor(): QueryTransformationPreProcessor
	{
		return new QueryTransformationPreProcessor(
			provider: $this->rag->resolveProvider(),
			transformation: QueryTransformationType::REWRITING
		);
	}

	private function getThresholdPostProcessor(): AdaptiveThresholdPostProcessor
	{
		return new AdaptiveThresholdPostProcessor(multiplier: 0.4);
	}
}
