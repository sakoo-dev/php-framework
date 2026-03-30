<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Doc;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Doc\Attributes\DontDocument;
use Sakoo\Framework\Core\Tests\TestCase;

final class DocTest extends TestCase
{
	#[Test]
	public function dont_document_attribute_targets_all(): void
	{
		$this->assertInstanceOf(DontDocument::class, new DontDocument());

		$attrs = (new \ReflectionClass(DontDocument::class))->getAttributes(\Attribute::class);

		$this->assertNotEmpty($attrs);
		$this->assertSame(\Attribute::TARGET_ALL, $attrs[0]->getArguments()[0] ?? null);
	}
}
