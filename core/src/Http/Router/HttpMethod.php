<?php

declare(strict_types=1);

namespace Sakoo\Framework\Core\Http\Router;

/**
 * Enumerates the standard HTTP methods used for route registration.
 *
 * Backed by uppercase strings matching the HTTP specification. Used by the
 * Router to enforce type-safe method constraints on route definitions.
 */
enum HttpMethod: string
{
	case GET = 'GET';

	case POST = 'POST';

	case PUT = 'PUT';

	case PATCH = 'PATCH';

	case DELETE = 'DELETE';

	case HEAD = 'HEAD';

	case OPTIONS = 'OPTIONS';
}
