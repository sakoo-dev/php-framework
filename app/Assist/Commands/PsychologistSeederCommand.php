<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\AI\Agent\PsychologistAgent;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\DataLoader\PdfReader;
use NeuronAI\RAG\Splitter\DelimiterTextSplitter;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use System\Path\Path;

class PsychologistSeederCommand extends Command
{
	public static function getName(): string
	{
		return 'psychologist:seeder';
	}

	public static function getDescription(): string
	{
		return 'Psychological Seeder';
	}

	public function run(Input $input, Output $output): int
	{
		$agent = PsychologistAgent::make();

		$output->block('Seeding...', Output::COLOR_CYAN);

		$documents = FileDataLoader::for(Path::getAppDir() . '/AI/Prompt/Seeder/02-dsm.pdf')
			->addReader('pdf', new PdfReader())
			->withSplitter(new DelimiterTextSplitter(separator: '.'))
			->getDocuments();

		foreach ($documents as $document) {
			$document->addMetadata('fact', 'dsm_book');
		}

		$agent->addDocuments(documents: $documents, chunkSize: 100);

		$output->block('Seeding succeed!', Output::COLOR_CYAN);

		return Output::SUCCESS;
	}
}
