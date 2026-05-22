<?php

declare(strict_types=1);

namespace App\AI\Neuron\Retry\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown by RetryProviderDecorator when all retry attempts are exhausted
 * without a successful response. Wraps the last caught exception as previous.
 */
final class MaxRetriesExceededException extends Exception {}
