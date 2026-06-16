<?php

declare(strict_types=1);

namespace App\AI\Mcp\Web\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when the HTTP fetch request fails due to network errors,
 * timeouts, or invalid URLs.
 */
final class WebFetchException extends Exception {}
