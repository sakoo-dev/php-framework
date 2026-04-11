<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Tests\TestCase;

final class HeaderBagTest extends TestCase
{
	#[Test]
	public function it_creates_from_array(): void
	{
		$bag = HeaderBag::fromArray(['Content-Type' => 'text/html']);

		$this->assertTrue($bag->has('Content-Type'));
		$this->assertSame(['text/html'], $bag->get('Content-Type'));
	}

	#[Test]
	public function it_performs_case_insensitive_lookup(): void
	{
		$bag = HeaderBag::fromArray(['Content-Type' => 'text/html']);

		$this->assertTrue($bag->has('content-type'));
		$this->assertTrue($bag->has('CONTENT-TYPE'));
		$this->assertSame(['text/html'], $bag->get('content-type'));
	}

	#[Test]
	public function it_preserves_original_casing(): void
	{
		$bag = HeaderBag::fromArray(['X-Custom-Header' => 'value']);
		$all = $bag->all();

		$this->assertArrayHasKey('X-Custom-Header', $all);
	}

	#[Test]
	public function it_returns_empty_array_for_missing_header(): void
	{
		$bag = new HeaderBag();

		$this->assertSame([], $bag->get('Missing'));
	}

	#[Test]
	public function it_returns_header_line(): void
	{
		$bag = HeaderBag::fromArray(['Accept' => ['text/html', 'application/json']]);

		$this->assertSame('text/html, application/json', $bag->getLine('Accept'));
	}

	#[Test]
	public function it_returns_empty_line_for_missing_header(): void
	{
		$bag = new HeaderBag();

		$this->assertSame('', $bag->getLine('Missing'));
	}

	#[Test]
	public function with_header_replaces_value(): void
	{
		$bag = HeaderBag::fromArray(['Content-Type' => 'text/html']);
		$new = $bag->withHeader('content-type', 'application/json');

		$this->assertSame(['text/html'], $bag->get('Content-Type'));
		$this->assertSame(['application/json'], $new->get('content-type'));
	}

	#[Test]
	public function with_added_header_appends_value(): void
	{
		$bag = HeaderBag::fromArray(['Accept' => ['text/html']]);
		$new = $bag->withAddedHeader('Accept', 'application/json');

		$this->assertSame(['text/html'], $bag->get('Accept'));
		$this->assertSame(['text/html', 'application/json'], $new->get('Accept'));
	}

	#[Test]
	public function with_added_header_creates_new_header_if_absent(): void
	{
		$bag = new HeaderBag();
		$new = $bag->withAddedHeader('X-New', 'value');

		$this->assertFalse($bag->has('X-New'));
		$this->assertTrue($new->has('X-New'));
	}

	#[Test]
	public function without_header_removes_header(): void
	{
		$bag = HeaderBag::fromArray(['Content-Type' => 'text/html', 'Accept' => '*/*']);
		$new = $bag->withoutHeader('content-type');

		$this->assertTrue($bag->has('Content-Type'));
		$this->assertFalse($new->has('Content-Type'));
		$this->assertTrue($new->has('Accept'));
	}

	#[Test]
	public function without_header_returns_same_instance_when_missing(): void
	{
		$bag = new HeaderBag();
		$new = $bag->withoutHeader('Missing');

		$this->assertSame($bag, $new);
	}
}
