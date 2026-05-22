<?php

declare(strict_types=1);

namespace App\AI\Neuron\Throttle\Exception;

use App\AI\Neuron\Exception\NonRetryableExceptionInterface;
use Sakoo\Framework\Core\Exception\Exception;

/**
 * Thrown by ThrottleProviderDecorator when the rate-limit window is exhausted
 * for a given composite key. The $retryAfterSeconds field tells the caller
 * how long to wait before retrying.
 */
final class ThrottleLimitExceededException extends Exception implements NonRetryableExceptionInterface
{
	public function __construct(
		public readonly int $retryAfterSeconds,
		string $message = '',
	) {
		parent::__construct(
			$message ?: "Rate limit exceeded. Retry after {$retryAfterSeconds}s.",
		);
	}
}
