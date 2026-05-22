<?php

declare(strict_types=1);

namespace App\AI\Neuron\CircuitBreaker\Exception;

use App\AI\Neuron\Exception\NonRetryableExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown when a CircuitBreaker is in the Open (or HalfOpen with no probe slot)
 * state and the call is rejected immediately without forwarding to the provider.
 */
final class CircuitOpenException extends Exception implements NonRetryableExceptionInterface {}
