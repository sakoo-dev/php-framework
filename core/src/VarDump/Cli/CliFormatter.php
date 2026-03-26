<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\VarDump\Cli;

use Sakoo\Framework\Core\Console\Output;
use Sakoo\Framework\Core\VarDump\Formatter;

/**
 * ANSI-coloured CLI formatter for debug-dumping PHP values.
 *
 * Produces a recursive, indented, type-annotated representation of any PHP value
 * and writes it to the console Output as a block. Type colouring follows these
 * conventions:
 *
 * - Strings   → yellow, wrapped in double quotes
 * - Integers / floats → red
 * - Booleans  → green ("true" or "false")
 * - Null      → red ("null")
 * - Arrays    → cyan label with element count, each key in green, values recursive
 * - Objects   → magenta class name, each property with +/- visibility prefix, values recursive
 *
 * Recursion depth is tracked via $depth so nested arrays and objects receive
 * proportionally increased indentation.
 */
readonly class CliFormatter implements Formatter
{
	public function __construct(private Output $output) {}

	/**
	 * Formats $value and writes the result as a console block.
	 */
	public function format(mixed $value): void
	{
		$this->output->write($this->formatType($value) . PHP_EOL);
	}

	/**
	 * Dispatches $value to the appropriate type-specific formatter and returns the
	 * coloured string representation. $depth controls the current indentation level
	 * for recursive calls on arrays and objects.
	 */
	protected function formatType(mixed $value, int $depth = 0): string
	{
		return match (gettype($value)) {
			'string' => $this->output->formatText('"' . $value . '"', Output::COLOR_YELLOW),
			'integer', 'double' => $this->output->formatText("$value", Output::COLOR_RED),
			'boolean' => $this->output->formatText($value ? 'true' : 'false', Output::COLOR_GREEN),
			'NULL' => $this->output->formatText('null', Output::COLOR_RED),
			'array' => $this->formatArray($value, $depth),
			'object' => $this->formatObject($value, $depth),
			// @phpstan-ignore argument.type
			default => $this->output->formatText(strval($value)),
		};
	}

	/**
	 * Renders an array as "Array(N) [\n  [key] => value\n]", recursing into each
	 * element with $depth + 1 for proper indentation.
	 *
	 * @phpstan-ignore missingType.iterableValue
	 */
	private function formatArray(array $value, int $depth): string
	{
		$indent = str_repeat("\t", $depth);
		$out = $this->output->formatText('Array', Output::COLOR_CYAN) . '(' . count($value) . ") [\n";

		foreach ($value as $key => $val) {
			$out .= $indent . '  [' . $this->output->formatText("$key", Output::COLOR_GREEN) . '] => ' . $this->formatType($val, $depth + 1) . "\n";
		}

		return $out . $indent . ']';
	}

	/**
	 * Renders an object as "object(ClassName) {\n  +/-type propName: value\n}", using
	 * reflection to enumerate all declared properties. Private properties are prefixed
	 * with "-", public (and protected) properties with "+". Values are recursed with
	 * $depth + 1.
	 */
	private function formatObject(object $value, int $depth): string
	{
		$indent = str_repeat("\t", $depth);
		$class = $value::class;
		$out = $this->output->formatText("object($class)", Output::COLOR_MAGENTA) . " {\n";

		$reflectionClass = new \ReflectionClass($value);

		foreach ($reflectionClass->getProperties() as $property) {
			$type = '';

			if ($property->getType() instanceof \ReflectionNamedType) {
				$type = $property->getType()->getName();
			}

			$out .= $indent . '  ' . ($property->isPrivate() ? '-' : '+') . $type . ' ' . $this->output->formatText($property->getName(), Output::COLOR_GREEN) . ': ' . $this->formatType($property->getValue($value), $depth + 1) . "\n";
		}

		return $out . $indent . '}';
	}
}
