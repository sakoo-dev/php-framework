<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Router;

use Psr\Http\Server\MiddlewareInterface;
use Sakoo\Framework\Core\Regex\Regex;

/**
 * Immutable value object representing a single route definition.
 *
 * Binds an HTTP method and a URI pattern to a controller action. The handler
 * is always a class name; the optional action specifies which method to call.
 * When action is null, the controller's __invoke() is used (single-action).
 *
 * The pattern supports named placeholders in the form {name} which are
 * captured as request attributes during matching.
 */
final readonly class Route
{
	private const string PLACEHOLDER_OPEN = '{';
	private const string PLACEHOLDER_CLOSE = '}';
	private const string SEGMENT_SEPARATOR = '/';

	/** @var array<class-string<MiddlewareInterface>> */
	public array $middleware;

	/**
	 * @param class-string                             $handler
	 * @param array<class-string<MiddlewareInterface>> $middleware
	 */
	public function __construct(
		public HttpMethod $method,
		public string $pattern,
		public string $handler,
		public ?string $action = null,
		array $middleware = [],
	) {
		$this->middleware = $middleware;
	}

	/**
	 * Attempts to match $path against this route's pattern. Returns the
	 * captured parameters on success, or null when the path does not match.
	 *
	 * @return null|array<string, string>
	 */
	public function match(string $path): ?array
	{
		$regex = $this->buildPattern();

		if (!$regex->test($path)) {
			return null;
		}

		$matches = $regex->match($path);

		/** @var array<string, string> $filtered */
		$filtered = array_filter(
			$matches,
			static fn (int|string $key): bool => !is_numeric($key),
			ARRAY_FILTER_USE_KEY,
		);

		return $filtered;
	}

	/**
	 * Converts a route pattern like /users/{id}/posts/{postId} into a
	 * Regex with named capture groups.
	 *
	 * {name} placeholders become (?P<name>[^/]+) capture groups.
	 * Literal segments are escaped via safeAdd() so slashes and other
	 * PCRE metacharacters don't conflict with the / delimiter.
	 */
	private function buildPattern(): Regex
	{
		$regex = new Regex();
		$regex->startOfLine();

		$segments = explode(self::PLACEHOLDER_OPEN, $this->pattern);

		foreach ($segments as $segment) {
			if (str_contains($segment, self::PLACEHOLDER_CLOSE)) {
				[$name, $rest] = explode(self::PLACEHOLDER_CLOSE, $segment, 2);

				$regex->add('(?P<' . $name . '>')
					->somethingWithout(fn (Regex $exp) => $exp->safeAdd(self::SEGMENT_SEPARATOR))
					->add(')');

				if ('' !== $rest) {
					$regex->safeAdd($rest);
				}
			} else {
				$regex->safeAdd($segment);
			}
		}

		$regex->endOfLine();

		return $regex;
	}
}
