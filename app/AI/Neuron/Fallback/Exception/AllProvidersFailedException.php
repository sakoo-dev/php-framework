<?php

declare(strict_types=1);

namespace App\AI\Neuron\Fallback\Exception;

use App\AI\Neuron\Exception\NonRetryableExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown by FallbackProviderDecorator when every provider in the chain has
 * failed. Wraps the last-caught exception as its previous so the original
 * failure remains inspectable.
 */
final class AllProvidersFailedException extends Exception implements NonRetryableExceptionInterface {}
