<?php

declare(strict_types=1);

namespace App\Assist\Tests;

use App\Assist\AI\Mcp\McpElements;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Assert\Exception\InvalidArgumentException;
use System\Testing\TestCase;

final class McpElementsPromptTest extends TestCase
{
	#[Test]
	public function dev_task_prompt_rejects_path_traversal_outside_prompt_dir(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Prompt path escapes/');

		(new McpElements())->devTaskPrompt('../../../../composer.json');
	}

	#[Test]
	public function dev_task_prompt_accepts_story_prompt_inside_prompt_dir(): void
	{
		$messages = (new McpElements())->devTaskPrompt('Story/05-workflow-module.md');

		$this->assertCount(2, $messages);
	}
}
