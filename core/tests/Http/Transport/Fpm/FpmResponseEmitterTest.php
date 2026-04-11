<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Http\Transport\Fpm;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Http\HeaderBag;
use Sakoo\Framework\Core\Http\Response;
use Sakoo\Framework\Core\Http\Stream;
use Sakoo\Framework\Core\Http\Transport\Fpm\FpmResponseEmitter;
use Sakoo\Framework\Core\Tests\TestCase;

#[Group('integration')]
final class FpmResponseEmitterTest extends TestCase
{
	#[Test]
	public function it_emits_body_content(): void
	{
		$response = new Response(
			200,
			'',
			HeaderBag::fromArray(['Content-Type' => 'text/plain']),
			Stream::createFromString('Hello FPM'),
		);

		$emitter = new FpmResponseEmitter();

		ob_start();
		@$emitter->emit($response);
		$output = ob_get_clean();

		$this->assertSame('Hello FPM', $output);
	}
}
