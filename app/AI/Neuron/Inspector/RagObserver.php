<?php

declare(strict_types=1);

namespace App\AI\Neuron\Inspector;

use NeuronAI\Observability\Events\Retrieved;
use NeuronAI\Observability\ObserverInterface;
use Sakoo\Framework\Core\Console\Output;

final class RagObserver implements ObserverInterface
{
	public function __construct(private Output $output) {}

	public function onEvent(string $event, object $source, mixed $data = null): void
	{
		//		$this->output->newLine();
		//		$this->output->block((string) (!$data ?: get_class((object) $data)), Output::BG_MAGENTA);

		if ($data instanceof Retrieved) {
			$this->output->block('RAG Query: ' . $data->question->getContent(), Output::COLOR_BLUE);

			foreach ($data->documents as $document) {
				$this->output->block('RAG Retrieved from ' . $document->getSourceName() . ' [' . $document->getSourceType() . '] - Score: ' . $document->getScore(), Output::COLOR_BLUE);
				$this->output->block('Retrived: ' . $document->getContent(), Output::COLOR_BLUE);
				$this->output->block('-----------------------------', Output::COLOR_BLUE);
			}
		}
	}
}
