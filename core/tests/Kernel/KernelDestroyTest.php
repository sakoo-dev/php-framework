<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Tests\Kernel;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Sakoo\Framework\Core\Kernel\Environment;
use Sakoo\Framework\Core\Kernel\Exceptions\KernelIsNotStartedException;
use Sakoo\Framework\Core\Kernel\Exceptions\KernelTwiceCallException;
use Sakoo\Framework\Core\Kernel\Kernel;
use Sakoo\Framework\Core\Kernel\Mode;
use Sakoo\Framework\Core\Path\Path;
use Sakoo\Framework\Core\Tests\TestCase;

final class KernelDestroyTest extends TestCase
{
	/**
	 * Returns the core service loaders array for use in inline kernel boots
	 * within individual test methods that need to re-prepare the kernel.
	 *
	 * @return array<class-string>
	 */
	private static function getCoreLoaders(): array
	{
		return require Path::getCoreDir() . '/ServiceLoader/Loaders.php';
	}

	#[Test]
	public function destroy_resets_singleton_so_prepare_can_be_called_again(): void
	{
		Kernel::destroy();

		Kernel::prepare(Mode::Test, Environment::Debug)
			->setServiceLoaders(self::getCoreLoaders())
			->run();

		$this->assertInstanceOf(Kernel::class, Kernel::getInstance());
		$this->assertTrue(Kernel::getInstance()->isInTestMode());

		Kernel::destroy();
		self::runKernel();
	}

	#[Test]
	public function destroy_makes_get_instance_throw(): void
	{
		Kernel::destroy();

		$this->throwsException(fn () => Kernel::getInstance())
			->withType(KernelIsNotStartedException::class)
			->validate();

		self::runKernel();
	}

	#[Test]
	public function destroy_allows_fresh_kernel_with_different_environment(): void
	{
		Kernel::destroy();

		Kernel::prepare(Mode::Test, Environment::Production)
			->setServiceLoaders(self::getCoreLoaders())
			->run();

		$this->assertTrue(Kernel::getInstance()->isInProductionEnv());
		$this->assertFalse(Kernel::getInstance()->isInDebugEnv());

		Kernel::destroy();
		self::runKernel();
	}

	#[Test]
	public function prepare_throws_without_prior_destroy(): void
	{
		$this->throwsException(fn () => Kernel::prepare(Mode::Test, Environment::Debug))
			->withType(KernelTwiceCallException::class)
			->validate();
	}

	#[Test]
	public function destroy_is_idempotent_when_no_kernel_exists(): void
	{
		Kernel::destroy();
		Kernel::destroy();

		$this->throwsException(fn () => Kernel::getInstance())
			->withType(KernelIsNotStartedException::class)
			->validate();

		self::runKernel();
	}

	#[Test]
	public function destroy_clears_container_bindings(): void
	{
		$this->assertTrue(kernel()->getContainer()->has(LoggerInterface::class));

		Kernel::destroy();

		$this->throwsException(fn () => Kernel::getInstance())
			->withType(KernelIsNotStartedException::class)
			->validate();

		self::runKernel();

		$this->assertTrue(kernel()->getContainer()->has(LoggerInterface::class));
	}
}
