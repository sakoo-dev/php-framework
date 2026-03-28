<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Doc\Object\Method;

use Sakoo\Framework\Core\Doc\Object\Class\ClassInterface;
use Sakoo\Framework\Core\Doc\Object\PhpDoc\PhpDocObject;
use Sakoo\Framework\Core\Doc\Object\tag;

/**
 * Parsed value object representing a virtual method declared via a [at-sign]method PHPDoc tag.
 *
 * PHP classes can document methods that do not exist in source using [at-sign]method tags,
 * typically on classes that use __call() magic. This class parses such a tag line
 * into structured data (name, return type, parameters, static flag, description) so
 * the documentation generator can render virtual methods alongside real ones.
 *
 * Parse rules (applied by parse() during construction):
 * - Leading '[at-sign]method' is stripped.
 * - An optional 'static' keyword sets isStatic = true.
 * - An optional return-type token precedes the method name.
 * - Parameters inside parentheses are extracted and further parsed by parseParams().
 * - Trailing text after the closing ')' is stored as the description.
 *
 * Throws InvalidVirtualMethodDefinitionException when the line is malformed
 * (missing parentheses, unbalanced brackets).
 */
class VirtualMethodObject implements MethodInterface
{
	private bool $isStatic = false;
	private ?string $returnType = null;
	private string $methodName = '';
	private ?string $description = null;
	/** @var array<array<string, null|string>> */
	private array $params = [];

	/**
	 * @throws InvalidVirtualMethodDefinitionException when $line cannot be parsed
	 */
	public function __construct(private ClassInterface $class, private string $line)
	{
		$this->parse();
	}

	/**
	 * Returns the ClassInterface that declared this [at-sign]method tag.
	 */
	public function getClass(): ClassInterface
	{
		return $this->class;
	}

	/**
	 * Returns the parsed method name.
	 */
	public function getName(): string
	{
		return $this->methodName;
	}

	/**
	 * Always returns false — virtual methods declared in PHPDoc are implicitly public.
	 */
	public function isPrivate(): bool
	{
		return false;
	}

	/**
	 * Always returns false — virtual methods declared in PHPDoc are implicitly public.
	 */
	public function isProtected(): bool
	{
		return false;
	}

	/**
	 * Always returns true — virtual methods are public by convention.
	 */
	public function isPublic(): bool
	{
		return true;
	}

	/**
	 * Returns true when the [at-sign]method tag included the 'static' keyword.
	 */
	public function isStatic(): bool
	{
		return $this->isStatic;
	}

	/**
	 * Returns true when the parsed method name is '__construct'.
	 */
	public function isConstructor(): bool
	{
		return '__construct' === $this->methodName;
	}

	/**
	 * Returns true when the method name starts with '__'.
	 */
	public function isMagicMethod(): bool
	{
		return str_starts_with($this->methodName, '__');
	}

	/**
	 * Returns the parsed return type string, or null when none was declared.
	 */
	public function getMethodReturnTypes(): ?string
	{
		return $this->returnType;
	}

	public function getRawDoc(): string
	{
		return $this->description ?: '';
	}

	public function getPhpDocObject(): PhpDocObject
	{
		return new PhpDocObject($this);
	}

	/**
	 * Returns the modifier names for this virtual method (['public'] or
	 * ['public', 'static'] when declared static).
	 *
	 * @return string[]
	 */
	public function getModifiers(): array
	{
		$modifiers = ['public'];

		if ($this->isStatic) {
			$modifiers[] = 'static';
		}

		return $modifiers;
	}

	/**
	 * Always returns false — virtual methods exist only in PHPDoc, not in framework source.
	 */
	public function isFrameworkFunction(): bool
	{
		return false;
	}

	/**
	 * Returns a comma-separated string of the non-null default values parsed from
	 * the parameter list, used in call-site usage examples.
	 */
	public function getDefaultValues(): string
	{
		$defaults = array_filter(array_column($this->params, 'default'));

		return implode(', ', $defaults);
	}

	/**
	 * Returns a pipe-joined string of the non-null parameter types parsed from the
	 * parameter list, used in method contract examples.
	 */
	public function getDefaultValueTypes(): string
	{
		$types = array_filter(array_column($this->params, 'type'));

		return implode('|', $types);
	}

	/**
	 * Returns true when the description contains '@internal', excluding the method
	 * from generated documentation.
	 */
	public function shouldNotDocument(): bool
	{
		return str_contains($this->description ?? '', '@internal');
	}

	/**
	 * Returns true when this virtual method is a public static named constructor
	 * (returns self, static, or its own name).
	 */
	public function isStaticInstantiator(): bool
	{
		return $this->isStatic()
			&& $this->isPublic()
			&& in_array($this->getMethodReturnTypes(), ['self', 'static', $this->getName()], true);
	}

	/**
	 * Parses the raw [at-sign]method tag line into the instance's fields.
	 *
	 * @throws InvalidVirtualMethodDefinitionException when parentheses are missing or unbalanced
	 */
	private function parse(): void
	{
		$this->line = trim(substr($this->line, strlen('@method')));

		if (str_starts_with($this->line, 'static ')) {
			$this->isStatic = true;
			$this->line = trim(substr($this->line, strlen('static')));
		}

		$parts = explode(' ', $this->line, 2);

		if (2 === count($parts) && $this->isTypeLike($parts[0])) {
			$this->returnType = $parts[0];
			$this->line = trim($parts[1]);
		}

		$parenPos = strpos($this->line, '(');

		throwIf(false === $parenPos, new InvalidVirtualMethodDefinitionException());

		$afterParen = substr($this->line, $parenPos + 1);
		$closeParenPos = strpos($afterParen, ')');

		throwIf(false === $closeParenPos, new InvalidVirtualMethodDefinitionException());

		// @phpstan-ignore argument.type
		$paramSection = substr($afterParen, 0, $closeParenPos);
		// @phpstan-ignore argument.type
		$this->methodName = trim(substr($this->line, 0, $parenPos));
		$this->description = trim(substr($afterParen, $closeParenPos + 1)) ?: null;
		$this->params = $this->parseParams($paramSection);
	}

	/**
	 * Parses a comma-separated parameter section string into a list of records,
	 * each with 'type', 'name', and 'default' keys (all nullable strings).
	 *
	 * @return array<array<string, null|string>>
	 */
	private function parseParams(string $paramSection): array
	{
		$params = [];
		$rawParams = array_filter(array_map('trim', explode(',', $paramSection)));

		foreach ($rawParams as $param) {
			$type = null;
			$name = null;
			$default = null;
			$tokens = preg_split('/\s+/', $param);

			if ($tokens) {
				foreach ($tokens as $token) {
					if (str_starts_with($token, '$')) {
						$name = ltrim($token, '$');
					} elseif (str_contains($token, '=')) {
						[$before, $after] = explode('=', $token, 2);
						$default = trim($after);

						if ($this->isTypeLike($before)) {
							$type = trim($before);
						}
					} elseif ($this->isTypeLike($token)) {
						$type = $token;
					}
				}
			}

			if ($name || $type || $default) {
				$params[] = ['type' => $type, 'name' => $name, 'default' => $default];
			}
		}

		return $params;
	}

	/**
	 * Returns true when $token looks like a PHP type name: after stripping leading
	 * '?', '[]', and '\' characters, the first character is alphabetic.
	 */
	private function isTypeLike(string $token): bool
	{
		$token = trim($token, '?[]\\');

		return ctype_alpha($token[0] ?? '');
	}
}
