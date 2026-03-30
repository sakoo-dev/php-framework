<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Kernel\Handlers;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Kernel\Handlers\ExceptionHandler;
use Sakoo\Framework\Core\Tests\TestCase;

final class ExceptionHandlerTest extends TestCase
{
	#[Test]
	public function it_rethrows_the_given_exception(): void
	{
		$handler = new ExceptionHandler();
		$original = new \RuntimeException('boom');

		ob_start();

		try {
			$handler($original);
			ob_end_clean();
			$this->fail('Expected exception to be re-thrown');
		} catch (\RuntimeException $caught) {
			ob_end_clean();
			$this->assertSame($original, $caught);
			$this->assertSame('boom', $caught->getMessage());
		}
	}
}
