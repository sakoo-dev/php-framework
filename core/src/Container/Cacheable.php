<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Container;

use Sakoo\Framework\Core\Container\Exceptions\ClassNotFoundException;
use Sakoo\Framework\Core\Container\Exceptions\ClassNotInstantiableException;
use Sakoo\Framework\Core\Container\Exceptions\ContainerCacheException;
use Sakoo\Framework\Core\Container\Parameter\ParameterSet;

/**
 * Provides PHP-file-based cache persistence for the Container's binding maps.
 *
 * The generated cache file is a plain PHP script that returns an associative array
 * with two keys — 'bindings' and 'singletons' — each containing the serialised
 * factory map. Factories are converted to inline PHP expressions (e.g.
 * "new \Foo\Bar($dep1, $dep2)") so the cache file can be included with no runtime
 * reflection overhead.
 *
 * This trait is mixed into Container and relies on the $cachePath and $bindings /
 * $singletons properties defined there. All public methods satisfy the ShouldCache
 * interface contract.
 */
trait Cacheable
{
	/**
	 * Returns the opening lines of the generated PHP cache file.
	 */
	private function prepareGenerateCache(): string
	{
		return '<?php' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL;
	}

	/**
	 * Serialises a single mapping list (bindings or singletons) into the PHP array
	 * syntax used inside the cache file.
	 *
	 * @param array<callable|object|string> $mappingList
	 */
	private function doGenerateCache(string $key, array $mappingList): string
	{
		$content = "\t'$key' => [" . PHP_EOL;

		foreach ($mappingList as $id => $value) {
			$content .= "\t\t" . var_export($id, true) . ' => ' . print_r($value, true) . ',' . PHP_EOL;
		}
		$content .= "\t" . '],' . PHP_EOL;

		return $content;
	}

	/**
	 * Returns the closing lines of the generated PHP cache file.
	 */
	private function postGenerateCache(): string
	{
		return '];' . PHP_EOL;
	}

	/**
	 * Loads the persisted binding and singleton maps from the cache file into the
	 * container's internal state, skipping all reflection and autowiring on boot.
	 *
	 * @throws \Throwable when no cache file exists at the configured path
	 */
	public function loadCache(): void
	{
		throwUnless($this->cacheExists(), new ContainerCacheException('Cache does not exist'));

		/** @var null|array<callable|object|string>[] $data */
		$data = include "$this->cachePath/container.cache.php";

		if (isset($data['bindings'], $data['singletons'])) {
			$this->bindings = $data['bindings'];
			$this->singletons = $data['singletons'];
		}
	}

	/**
	 * Deletes the cache file from disk. Returns true on success, false when no
	 * cache file was present.
	 */
	public function flushCache(): bool
	{
		if ($this->cacheExists()) {
			return unlink("$this->cachePath/container.cache.php");
		}

		return false;
	}

	/**
	 * Converts a single factory value into an inline PHP expression string suitable
	 * for embedding in the cache file.
	 *
	 * Strings that are class names are resolved to "new \ClassName(...deps)" expressions
	 * via getClassFactory(). Objects are treated as their class. Callables are
	 * var_exported. All other values are returned as-is.
	 *
	 * @throws \Throwable
	 * @throws \ReflectionException
	 * @throws ClassNotInstantiableException
	 * @throws ClassNotFoundException
	 */
	private function getTypeFactory(mixed $factory): mixed
	{
		return match (true) {
			is_string($factory) && empty($factory) => "''",
			is_string($factory) && class_exists($factory) => $this->getClassFactory($factory),
			is_object($factory) => $this->getClassFactory($factory::class),
			is_callable($factory) => var_export($factory, true),
			default => $factory,
		};
	}

	/**
	 * Produces a "new \ClassName($dep1, $dep2, ...)" expression string for $class,
	 * resolving each constructor dependency recursively through getTypeFactory() so
	 * the resulting cache expression is fully self-contained.
	 *
	 * @param class-string $class
	 *
	 * @throws \Throwable
	 * @throws ClassNotInstantiableException
	 * @throws \ReflectionException
	 * @throws ClassNotFoundException
	 */
	private function getClassFactory(string $class): string
	{
		$reflection = new \ReflectionClass($class);

		throwUnless($reflection->isInstantiable(), new ClassNotInstantiableException());

		$constructor = $reflection->getConstructor();

		if (!$this->canAutowire($constructor)) {
			return "new \\{$class}()";
		}

		/** @var \ReflectionMethod $constructor */
		$parameterSet = new ParameterSet($this);
		$params = $parameterSet->resolve($constructor->getParameters());
		$dependencies = array_map(fn ($param) => $this->getTypeFactory($param), $params);

		return "new \\{$class}(" . implode(', ', $dependencies) . ')';
	}

	/**
	 * Returns true when a cache file exists at the configured cache path.
	 */
	public function cacheExists(): bool
	{
		return !is_null($this->cachePath) && file_exists("$this->cachePath/container.cache.php");
	}

	/**
	 * Serialises all current bindings and singletons to a PHP cache file, flushing
	 * any stale cache beforehand. Throws when no cache path was configured on the
	 * container.
	 *
	 * @throws \Throwable
	 * @throws \ReflectionException
	 * @throws ClassNotInstantiableException
	 * @throws ClassNotFoundException
	 */
	public function dumpCache(): void
	{
		throwIf(is_null($this->cachePath), new ContainerCacheException('Cache is not enabled'));

		/** @var array<callable|object|string> $bindings */
		$bindings = array_map(fn ($factory) => $this->getTypeFactory($factory), $this->bindings);
		/** @var array<callable|object|string> $singletons */
		$singletons = array_map(fn ($factory) => $this->getTypeFactory($factory), $this->singletons);

		$this->flushCache();

		$content = $this->prepareGenerateCache();
		$content .= $this->doGenerateCache('bindings', $bindings);
		$content .= $this->doGenerateCache('singletons', $singletons);
		$content .= $this->postGenerateCache();

		file_put_contents("$this->cachePath/container.cache.php", $content);
	}
}
