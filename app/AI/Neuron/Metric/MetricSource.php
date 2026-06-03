<?php

declare(strict_types=1);

namespace App\AI\Neuron\Metric;

/**
 * Indicates whether a response was served from a live provider call or a cache hit.
 * Stored in MetricEntry::$source and drives cost/latency dashboard segmentation.
 */
enum MetricSource: string
{
	case Live = 'Live';
	case Cache = 'Cache';
}
