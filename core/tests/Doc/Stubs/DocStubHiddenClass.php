<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Doc\Stubs;

use Sakoo\Framework\Core\Doc\Attributes\DontDocument;

#[DontDocument]
final class DocStubHiddenClass
{
	public function doSomething(): void {}
}
