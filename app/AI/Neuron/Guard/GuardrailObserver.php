<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard;

use App\AI\Neuron\Guard\Exception\GuardViolationException;
use App\AI\Neuron\Session\SessionId;
use NeuronAI\Observability\Events\InferenceStart;
use NeuronAI\Observability\Events\InferenceStop;
use NeuronAI\Observability\ObserverInterface;

/**
 * NeuronAI observer that guards every inbound request and outbound response.
 *
 * On InferenceStart:
 *   - Runs the pipeline against the user message text.
 *   - If Restricted: audits and throws GuardViolationException — inference aborts.
 *   - If PII was masked (safeText differs): audits the masking. Note — NeuronAI
 *     Message objects are immutable; the masked text is recorded in the audit
 *     but the original message content cannot be replaced in-flight via the
 *     observer hook. PII masking enforcement at the provider boundary requires
 *     a custom provider decorator (see GuardrailProviderDecorator, Task 2).
 *   - Otherwise: audits normally and inference continues unchanged.
 *
 * On InferenceStop (non-streaming chat() path only):
 *   - Runs the pipeline against the full assembled response text.
 *   - If Restricted: audits and throws GuardViolationException.
 *   - Otherwise: audits if classification is non-Public.
 *
 * For the streaming path, AgentCommand buffers all chunks via BufferedStream,
 * then calls guardText() before rendering. This makes response guardrailing
 * fully enforceable for streams — no chunk is displayed until the full response
 * passes the pipeline.
 */
final class GuardrailObserver implements ObserverInterface
{
	public const RESPONSE = 'response';
	public const REQUEST = 'request';

	public function __construct(
		private readonly GuardrailPipeline $pipeline,
		private readonly AuditStorageInterface $auditStorage,
		private readonly SessionId $sessionId,
		private readonly string $agentName,
	) {}

	public function onEvent(string $event, object $source, mixed $data = null, ?string $branchId = null): void
	{
		if ($data instanceof InferenceStart) {
			$this->guardRequest($data);
		}

		if ($data instanceof InferenceStop) {
			$this->guardResponse($data);
		}
	}

	/**
	 * Runs the pipeline against an arbitrary text string.
	 *
	 * Used by AgentCommand after buffering a full stream to enforce response
	 * guardrailing before any chunk is rendered to the user.
	 *
	 * @throws GuardViolationException
	 */
	public function guardText(string $text, string $direction): void
	{
		try {
			$result = $this->pipeline->run($text);
			$this->audit($direction, $result->classification, $result->combinedReason(), $result->safeText);
		} catch (GuardViolationException $e) {
			$this->audit($direction, $e->classification, $e->reason, null);

			throw $e;
		}
	}

	private function guardRequest(InferenceStart $data): void
	{
		$this->guardText($data->message->getContent() ?: '', self::REQUEST);
	}

	private function guardResponse(InferenceStop $data): void
	{
		$this->guardText($data->response->getContent() ?: '', self::RESPONSE);
	}

	private function audit(string $direction, ContentClassification $classification, string $reason, ?string $safeText): void
	{
		$this->auditStorage->store(new AuditEntry(
			sessionId: $this->sessionId->value,
			timestamp: date('c'),
			agent: $this->agentName,
			direction: $direction,
			classification: $classification,
			reason: $reason,
			safeText: $classification->isBlocking() ? null : $safeText,
		));
	}
}
