<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\Router\HttpMethod;
use Sakoo\Framework\Core\Tests\TestCase;

/**
 * Unit tests for HttpMethod — a backed string enum covering all RFC-standard verbs.
 */
final class HttpMethodTest extends TestCase
{
	#[Test]
	public function all_cases_have_correct_backed_values(): void
	{
		$this->assertSame('GET', HttpMethod::GET->value);
		$this->assertSame('POST', HttpMethod::POST->value);
		$this->assertSame('PUT', HttpMethod::PUT->value);
		$this->assertSame('PATCH', HttpMethod::PATCH->value);
		$this->assertSame('DELETE', HttpMethod::DELETE->value);
		$this->assertSame('HEAD', HttpMethod::HEAD->value);
		$this->assertSame('OPTIONS', HttpMethod::OPTIONS->value);
	}

	#[Test]
	public function from_returns_correct_case_for_each_verb(): void
	{
		$this->assertSame(HttpMethod::GET, HttpMethod::from('GET'));
		$this->assertSame(HttpMethod::POST, HttpMethod::from('POST'));
		$this->assertSame(HttpMethod::PUT, HttpMethod::from('PUT'));
		$this->assertSame(HttpMethod::PATCH, HttpMethod::from('PATCH'));
		$this->assertSame(HttpMethod::DELETE, HttpMethod::from('DELETE'));
		$this->assertSame(HttpMethod::HEAD, HttpMethod::from('HEAD'));
		$this->assertSame(HttpMethod::OPTIONS, HttpMethod::from('OPTIONS'));
	}

	#[Test]
	public function from_throws_on_unknown_value(): void
	{
		$this->expectException(\ValueError::class);
		HttpMethod::from('CONNECT');
	}

	#[Test]
	public function try_from_returns_null_on_unknown_value(): void
	{
		$this->assertNull(HttpMethod::tryFrom('TRACE'));
		$this->assertNull(HttpMethod::tryFrom('get'));
		$this->assertNull(HttpMethod::tryFrom(''));
	}

	#[Test]
	public function try_from_is_case_sensitive(): void
	{
		$this->assertNull(HttpMethod::tryFrom('post'));
		$this->assertNull(HttpMethod::tryFrom('Post'));
		$this->assertSame(HttpMethod::POST, HttpMethod::tryFrom('POST'));
	}

	#[Test]
	public function cases_returns_all_seven_methods(): void
	{
		$cases = HttpMethod::cases();

		$this->assertCount(7, $cases);
	}

	#[Test]
	public function enum_cases_are_identical_singletons(): void
	{
		$this->assertSame(HttpMethod::from('GET'), HttpMethod::GET);
	}
}
