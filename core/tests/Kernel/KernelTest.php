<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Kernel;

use PHPUnit\Framework\Attributes\Test;
use Sakoo\Framework\Core\Container\Contracts\ContainerInterface;
use Sakoo\Framework\Core\Kernel\Environment;
use Sakoo\Framework\Core\Kernel\Exceptions\KernelTwiceCallException;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Kernel\Mode;
use Sakoo\Framework\Core\Profiler\ProfilerInterface;
use Sakoo\Framework\Core\Tests\TestCase;

final class KernelTest extends TestCase
{
	#[Test]
	public function kernel_cannot_initiate_twice(): void
	{
		$this->expectException(KernelTwiceCallException::class);
		Kernel::prepare(Mode::HTTP, Environment::Production);
	}

	#[Test]
	public function kernel_exists_in_test(): void
	{
		$this->assertInstanceOf(Kernel::class, kernel());
		$this->assertEquals(Environment::Debug, kernel()->getEnvironment());
		$this->assertEquals(Mode::Test, kernel()->getMode());
		$this->assertInstanceOf(ContainerInterface::class, kernel()->getContainer());
		$this->assertInstanceOf(ProfilerInterface::class, kernel()->getProfiler());
	}

	#[Test]
	public function get_instance_returns_singleton(): void
	{
		$this->assertSame(kernel(), Kernel::getInstance());
	}

	#[Test]
	public function is_in_test_mode(): void
	{
		$this->assertTrue(kernel()->isInTestMode());
		$this->assertFalse(kernel()->isInHttpMode());
		$this->assertFalse(kernel()->isInConsoleMode());
	}

	#[Test]
	public function is_in_debug_environment(): void
	{
		$this->assertTrue(kernel()->isInDebugEnv());
		$this->assertFalse(kernel()->isInProductionEnv());
	}

	#[Test]
	public function set_replica_id_stores_the_value(): void
	{
		$kernel = kernel();

		$kernel->setReplicaId('replica-42');
		$this->assertSame('replica-42', $kernel->getReplicaId());

		$kernel->setReplicaId('');
	}
}
