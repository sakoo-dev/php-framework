<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

/**
 * Port for persisting audit entries. Mirrors MetricStorageInterface so the same
 * adapter pattern applies — swap JsonlAuditStorage for any DB-backed adapter by
 * rebinding this interface in AIServiceLoader.
 */
interface AuditStorageInterface
{
	public function store(AuditEntry $entry): void;
}
