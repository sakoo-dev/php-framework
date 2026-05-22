<?php

declare(strict_types=1);

namespace App\AI\Neuron\Cache\Exception;

use Sakoo\Framework\Core\Exception\Exception;

/** Thrown when the cache storage layer fails to read or write an entry. */
final class CacheStorageException extends Exception {}
