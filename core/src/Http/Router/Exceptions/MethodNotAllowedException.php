<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Router\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;
use Sakoo\Framework\Core\Http\Router\HttpMethod;

/**
 * Thrown when a route exists for the path but not for the requested HTTP
 * method (HTTP 405). Carries the list of methods that are allowed so
 * the caller can populate the Allow response header.
 */
class MethodNotAllowedException extends Exception
{
	/** @var HttpMethod[] */
	private readonly array $allowedMethods;

	/**
	 * @param HttpMethod[] $allowedMethods
	 */
	public function __construct(array $allowedMethods = [])
	{
		$this->allowedMethods = $allowedMethods;
		$methods = implode(', ', array_map(fn (HttpMethod $m): string => $m->value, $allowedMethods));

		parent::__construct("Method not allowed. Allowed methods: $methods", 405);
	}

	/**
	 * @return HttpMethod[]
	 */
	public function getAllowedMethods(): array
	{
		return $this->allowedMethods;
	}
}
