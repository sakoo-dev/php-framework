<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Formatter\PromptResultFormatter;
use Mcp\Capability\Formatter\ResourceResultFormatter;
use Mcp\Schema\Content\TextContent;

final class McpAttributeContextProvider implements McpContextProvider
{
	/**
	 * @var string[]
	 */
	private array $excluded = [];

	/**  @param class-string $sourceClass */
	public function __construct(private string $sourceClass) {}

	public function resolve(): array
	{
		$lines = [];

		/** @var \ReflectionClass<object> $reflectionClass */
		$reflectionClass = new \ReflectionClass($this->sourceClass);
		$reflectionMethods = $reflectionClass->getMethods();

		foreach ($reflectionMethods as $reflectionMethod) {
			$reflectionAttributes = $reflectionMethod->getAttributes();

			foreach ($reflectionAttributes as $reflectionAttribute) {
				/** @var \ReflectionAttribute<McpPrompt|McpResource> $reflectionAttribute */
				if (in_array($reflectionAttribute->getName(), $this->excluded)) {
					continue;
				}

				if (McpResource::class === $reflectionAttribute->getName()) {
					$lines[] = $this->resolveMcpResource($reflectionAttribute, $reflectionMethod, $reflectionClass);
				}

				if (McpPrompt::class === $reflectionAttribute->getName()) {
					$lines[] = $this->resolveMcpPrompt($reflectionAttribute, $reflectionMethod, $reflectionClass);
				}
			}
		}

		return $lines;
	}

	public function exclude(array $excluded): McpContextProvider
	{
		$this->excluded = $excluded;

		return $this;
	}

	/**
	 * @param \ReflectionAttribute<McpPrompt|McpResource> $reflectionAttribute
	 * @param \ReflectionClass<object>                    $reflectionClass
	 */
	private function resolveMcpResource(\ReflectionAttribute $reflectionAttribute, \ReflectionMethod $reflectionMethod, \ReflectionClass $reflectionClass): string
	{
		/** @var McpResource $resourceAttribute */
		$resourceAttribute = $reflectionAttribute->newInstance();

		$mcpResourceResult = $reflectionMethod->invoke($reflectionClass->newInstance());
		$formatter = new ResourceResultFormatter();

		$resource = '';

		foreach ($formatter->format($mcpResourceResult, $resourceAttribute->uri) as $mcpResource) {
			$extracted = json_encode($mcpResource->jsonSerialize());
			$resource .= "[Resource:{$resourceAttribute->uri}]\n$extracted";
		}

		return $resource;
	}

	/**
	 * @param \ReflectionAttribute<McpPrompt|McpResource> $reflectionAttribute
	 * @param \ReflectionClass<object>                    $reflectionClass
	 */
	private function resolveMcpPrompt(\ReflectionAttribute $reflectionAttribute, \ReflectionMethod $reflectionMethod, \ReflectionClass $reflectionClass): string
	{
		if (0 !== $reflectionMethod->getNumberOfParameters()) {
			return '';
		}

		/** @var McpPrompt $promptAttribute */
		$promptAttribute = $reflectionAttribute->newInstance();

		$mcpPromptResult = $reflectionMethod->invoke($reflectionClass->newInstance());
		$formater = new PromptResultFormatter();

		$prompt = '';

		foreach ($formater->format($mcpPromptResult) as $mcpPrompt) {
			$extracted = '';

			if (is_subclass_of($mcpPrompt->content, TextContent::class)) {
				/** @var string $extracted */
				$extracted = $mcpPrompt->content->text;
			}

			$prompt .= "[Prompt:{$promptAttribute->name}|{$mcpPrompt->role->value}]\n$extracted" . PHP_EOL;
		}

		return $prompt;
	}
}
