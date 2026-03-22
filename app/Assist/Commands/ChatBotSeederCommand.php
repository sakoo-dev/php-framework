<?php

declare(strict_types=1);

namespace App\Assist\Commands;

use App\Assist\AI\Agent\ChatBotAgent;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use Sakoo\Framework\Core\Console\Command;
use Sakoo\Framework\Core\Console\Input;
use Sakoo\Framework\Core\Console\Output;
use System\Path\Path;

class ChatBotSeederCommand extends Command
{
	public static function getName(): string
	{
		return 'chatbot:seeder';
	}

	public static function getDescription(): string
	{
		return 'Train RAG';
	}

	public function run(Input $input, Output $output): int
	{
		$bot = ChatBotAgent::make();

		$bot->addDocuments(
			FileDataLoader::for(Path::getAppDir() . '/Assist/AI/Prompt/chatbot-rag-train.md')->getDocuments(),
		);

		$output->block('Seeding succeed!', Output::COLOR_CYAN);

		return Output::SUCCESS;
	}
}
