<?php

declare(strict_types=1);

namespace App\AI\Neuron\Exception;

/**
 * Marker interface for exceptions that must not be retried.
 *
 * Tag any exception with this interface to prevent RetryProviderDecorator from
 * wasting attempts on errors that cannot succeed on retry — circuit open,
 * throttle exceeded, all providers exhausted, etc.
 */
interface NonRetryableExceptionInterface {}
