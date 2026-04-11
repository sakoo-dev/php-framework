<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Router\Exceptions;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when no route matches the requested URI path (HTTP 404).
 */
class RouteNotFoundException extends Exception
{
	public function __construct(string $path = '')
	{
		parent::__construct("No route found for path: $path", 404);
	}
}
