<?php

declare(strict_types=1);

namespace App\Assist\AI\Neuron\Session;

/**
 * Immutable value object representing a unique chat session identifier.
 *
 * A session ID ties together a chat history file, audit logs, and metric entries
 * so that all telemetry for a single conversation can be correlated. Generated
 * once per new session; deserialized from file names when resuming.
 */
final readonly class SessionId
{
	public function __construct(
		public readonly string $value,
	) {}

	public static function generate(): self
	{
		return new self(sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xFFFF),
			mt_rand(0, 0xFFFF),
			mt_rand(0, 0xFFFF),
			mt_rand(0, 0x0FFF) | 0x4000,
			mt_rand(0, 0x3FFF) | 0x8000,
			mt_rand(0, 0xFFFF),
			mt_rand(0, 0xFFFF),
			mt_rand(0, 0xFFFF),
		));
	}

	public static function fromString(string $value): self
	{
		return new self($value);
	}

	public function equals(self $other): bool
	{
		return $this->value === $other->value;
	}

	public function __toString(): string
	{
		return $this->value;
	}
}
