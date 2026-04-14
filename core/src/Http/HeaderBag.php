<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http;

/**
 * Case-insensitive header collection for PSR-7 messages.
 *
 * Stores headers in their original casing while performing all lookups via a
 * normalised lowercase key map. Immutable — every mutation returns a new
 * instance. Used internally by Message and its subtypes; not part of the
 * public PSR-7 surface.
 */
final readonly class HeaderBag
{
	/**
	 * @param array<string, string[]> $headers Original-case name → values
	 * @param array<string, string>   $nameMap Lowercase name → original-case name
	 */
	public function __construct(
		private array $headers = [],
		private array $nameMap = [],
	) {}

	/**
	 * Builds a HeaderBag from an associative array of name → value(s).
	 *
	 * @param array<string, string|string[]> $headers
	 */
	public static function fromArray(array $headers): self
	{
		$normalized = [];
		$nameMap = [];

		foreach ($headers as $name => $value) {
			$lower = mb_strtolower($name);
			$nameMap[$lower] = $name;
			$normalized[$name] = is_array($value) ? $value : [$value];
		}

		return new self($normalized, $nameMap);
	}

	public function has(string $name): bool
	{
		return isset($this->nameMap[mb_strtolower($name)]);
	}

	/**
	 * @return string[]
	 */
	public function get(string $name): array
	{
		$lower = mb_strtolower($name);

		if (!isset($this->nameMap[$lower])) {
			return [];
		}

		return $this->headers[$this->nameMap[$lower]];
	}

	public function getLine(string $name): string
	{
		return implode(', ', $this->get($name));
	}

	/**
	 * @return array<string, string[]>
	 */
	public function all(): array
	{
		return $this->headers;
	}

	/**
	 * @param string|string[] $value
	 */
	public function withHeader(string $name, array|string $value): self
	{
		$lower = mb_strtolower($name);
		$values = is_array($value) ? $value : [$value];

		$headers = $this->headers;
		$nameMap = $this->nameMap;

		if (isset($nameMap[$lower])) {
			unset($headers[$nameMap[$lower]]);
		}

		$nameMap[$lower] = $name;
		$headers[$name] = $values;

		return new self($headers, $nameMap);
	}

	/**
	 * @param string|string[] $value
	 */
	public function withAddedHeader(string $name, array|string $value): self
	{
		$lower = mb_strtolower($name);
		$values = is_array($value) ? $value : [$value];

		$headers = $this->headers;
		$nameMap = $this->nameMap;

		if (isset($nameMap[$lower])) {
			$originalName = $nameMap[$lower];
			$headers[$originalName] = array_merge($headers[$originalName], $values);
		} else {
			$nameMap[$lower] = $name;
			$headers[$name] = $values;
		}

		return new self($headers, $nameMap);
	}

	public function withoutHeader(string $name): self
	{
		$lower = mb_strtolower($name);

		if (!isset($this->nameMap[$lower])) {
			return $this;
		}

		$headers = $this->headers;
		$nameMap = $this->nameMap;

		unset($headers[$nameMap[$lower]], $nameMap[$lower]);

		return new self($headers, $nameMap);
	}
}
