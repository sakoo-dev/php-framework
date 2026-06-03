<?php

declare(strict_types=1);

namespace App\AI\Neuron\Guard\Detector;

use App\AI\Neuron\Guard\ContentClassification;
use App\AI\Neuron\Guard\DetectionResult;
use App\AI\Neuron\Guard\DetectionStrategyInterface;

/**
 * Masks personally identifiable information in-place before text reaches the LLM.
 *
 * PII patterns are sourced from PiiPatternDatasetInterface. Structural patterns
 * requiring callback validation (Luhn for credit cards, checksum for Iranian
 * national IDs) are validated here — the dataset holds only the regex.
 *
 * All matched PII types are reported as individual reasons in DetectionResult
 * so audit logs record exactly which PII categories were found and masked.
 *
 * Mask tokens:
 *   - Email address       → [EMAIL]
 *   - Phone number        → [PHONE]
 *   - IBAN                → [IBAN]
 *   - Credit card number  → [CARD]
 *   - Iranian national ID → [NID]
 *   - IPv4 address        → [IP]
 */
final class PiiMaskingDetector implements DetectionStrategyInterface
{
	public function detect(string $text): DetectionResult
	{
		$result = DetectionResult::clean($text);
		$current = $text;

		$current = $this->maskSimple($current, $this->email(), '[EMAIL]', 'email address', $result);
		$current = $this->maskSimple($current, $this->iban(), '[IBAN]', 'IBAN', $result);
		$current = $this->maskWithCallback($current, $this->creditCard(), '[CARD]', 'credit card number', $this->cardCallback(...), $result);
		$current = $this->maskWithCallback($current, $this->iranianNationalId(), '[NID]', 'Iranian national ID', $this->nidCallback(...), $result);
		$current = $this->maskWithCallback($current, $this->phone(), '[PHONE]', 'phone number', $this->phoneCallback(...), $result);
		$current = $this->maskSimple($current, $this->ipv4(), '[IP]', 'IPv4 address', $result);

		if (!$result->matched) {
			return $result;
		}

		return $result->withProcessedText($current);
	}

	private function maskSimple(
		string $text,
		string $pattern,
		string $token,
		string $label,
		DetectionResult &$result,
	): string {
		$replaced = preg_replace($pattern, $token, $text, -1, $count);

		if ($count > 0) {
			$result = $result->matched
				? $result->withReason(ContentClassification::Confidential, 'PII masked: ' . $label)
				: DetectionResult::flag(ContentClassification::Confidential, 'PII masked: ' . $label, $text);
		}

		return $replaced ?? $text;
	}

	/**
	 * @param callable(array<int|string, string>): bool $validator
	 */
	private function maskWithCallback(
		string $text,
		string $pattern,
		string $token,
		string $label,
		callable $validator,
		DetectionResult &$result,
	): string {
		$found = false;

		$masked = preg_replace_callback($pattern, function (array $m) use ($token, $validator, &$found): string {
			if ($validator($m)) {
				$found = true;

				return $token;
			}

			return $m[0];
		}, $text);

		if ($found) {
			$result = $result->matched
				? $result->withReason(ContentClassification::Confidential, 'PII masked: ' . $label)
				: DetectionResult::flag(ContentClassification::Confidential, 'PII masked: ' . $label, $text);
		}

		return $masked ?? $text;
	}

	/** @param array<int|string, string> $m */
	private function phoneCallback(array $m): bool
	{
		$digits = preg_replace('/\D/', '', $m[0]);

		$len = strlen($digits ?? '');

		return $len >= 10 && $len <= 13;
	}

	/** @param array<int|string, string> $m */
	private function cardCallback(array $m): bool
	{
		$digits = preg_replace('/\D/', '', $m[0]);

		return $this->passesLuhn($digits ?? '');
	}

	/** @param array<int|string, string> $m */
	private function nidCallback(array $m): bool
	{
		return $this->isValidIranianNationalId($m[0]);
	}

	private function passesLuhn(string $digits): bool
	{
		$length = strlen($digits);

		if ($length < 13 || $length > 19) {
			return false;
		}

		$sum = 0;
		$parity = $length % 2;

		for ($i = 0; $i < $length; ++$i) {
			$digit = (int) $digits[$i];

			if ($i % 2 === $parity) {
				$digit *= 2;

				if ($digit > 9) {
					$digit -= 9;
				}
			}

			$sum += $digit;
		}

		return 0 === $sum % 10;
	}

	private function isValidIranianNationalId(string $id): bool
	{
		if (preg_match('/^(\d)\1{9}$/', $id)) {
			return false;
		}

		$sum = 0;

		for ($i = 0; $i < 9; ++$i) {
			$sum += (int) $id[$i] * (10 - $i);
		}

		$remainder = $sum % 11;
		$check = (int) $id[9];

		return ($remainder < 2 && $check === $remainder) || ($remainder >= 2 && $check === 11 - $remainder);
	}

	public function email(): string
	{
		return '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/u';
	}

	/**
	 * Matches international or domestic phone numbers.
	 *
	 * Requires one of:
	 *   - A leading + (international prefix): +1 555 123 4567
	 *   - An opening parenthesis for area code: (021) 123 4567
	 *   - At least two consecutive digits before a separator, implying a
	 *     structured local number: 09121234567, 021-123-4567
	 *
	 * After stripping non-digits the detector enforces ≥ 10 digits, which
	 * eliminates dates, ZIP codes, and other short sequences.
	 */
	public function phone(): string
	{
		return '/(?<!\d)(\+\d[\d\s\-().]{8,14}|\(\d+\)[\d\s\-().]{6,12}|0\d{9,11}|\d{2}[\s\-]\d[\d\s\-]{6,10})(?!\d)/u';
	}

	/**
	 * Matches IBAN-shaped tokens: 2-letter country code, 2 check digits,
	 * followed by 4–30 alphanumeric characters.
	 */
	public function iban(): string
	{
		return '/\b[A-Z]{2}\d{2}[A-Z0-9]{4,30}\b/u';
	}

	/**
	 * Matches 13–19 digit sequences (with optional spaces or dashes between groups)
	 * that look like card numbers. Luhn validation is applied in the detector.
	 *
	 * Must be applied before phone() to prevent digit groups from being consumed
	 * by the broader phone pattern.
	 */
	public function creditCard(): string
	{
		return '/\b(?:\d[ \-]?){13,19}\b/u';
	}

	/**
	 * Matches any standalone 10-digit number as a candidate Iranian national ID.
	 * The detector validates the checksum digit before masking.
	 */
	public function iranianNationalId(): string
	{
		return '/\b\d{10}\b/u';
	}

	/**
	 * Matches valid dotted-decimal IPv4 addresses (0–255 per octet).
	 */
	public function ipv4(): string
	{
		return '/\b((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)\b/u';
	}
}
