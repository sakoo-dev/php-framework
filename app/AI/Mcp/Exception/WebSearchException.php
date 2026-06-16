<?php

declare(strict_types=1);

namespace App\AI\Mcp\Web\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when the Brave Search API key is missing, the HTTP call fails,
 * or the API returns an unexpected payload.
 */
final class WebSearchException extends Exception {}
