<?php

declare(strict_types=1);

namespace App\AI\Neuron\Evals\Guard;

use App\AI\Neuron\Evals\Guard\Assertions\HasReason;
use App\AI\Neuron\Evals\Guard\Assertions\IsCleanResult;
use App\AI\Neuron\Evals\Guard\Assertions\IsFlaggedAs;
use App\AI\Neuron\Evals\Guard\Assertions\IsMaskedWith;
use App\AI\Neuron\Guard\ContentClassification;
use App\AI\Neuron\Guard\Detector\PiiMaskingDetector;
use NeuronAI\Evaluation\BaseEvaluator;
use NeuronAI\Evaluation\Contracts\DatasetInterface;
use NeuronAI\Evaluation\Dataset\ArrayDataset;

/**
 * Evaluates PiiMaskingDetector across all supported PII categories.
 *
 * Each dataset item specifies:
 *   - input:       raw text to run through the detector
 *   - shouldMatch: whether masking is expected
 *   - token:       the expected mask token — only checked when shouldMatch is true
 *   - original:    the original PII value that must not appear in processedText
 *   - reason:      substring expected in the combined reason
 */
class PiiMaskingDetectorEval extends BaseEvaluator
{
	private PiiMaskingDetector $detector;

	public function setUp(): void
	{
		$this->detector = new PiiMaskingDetector();
	}

	public function getDataset(): DatasetInterface
	{
		return new ArrayDataset([
			// ── Email ─────────────────────────────────────────────────────────
			[
				'input' => 'Contact me at ali.tehrani@example.com for details.',
				'shouldMatch' => true, 'token' => '[EMAIL]', 'original' => 'ali.tehrani@example.com', 'reason' => 'email address',
			],
			[
				'input' => 'Reach the team at support@sakoo.io or sales@sakoo.io',
				'shouldMatch' => true, 'token' => '[EMAIL]', 'original' => 'support@sakoo.io', 'reason' => 'email address',
			],
			[
				'input' => 'My work email is m.rezaei+newsletter@company.co.ir',
				'shouldMatch' => true, 'token' => '[EMAIL]', 'original' => 'm.rezaei+newsletter@company.co.ir', 'reason' => 'email address',
			],
			[
				'input' => 'Contact the admin panel team about the issue.',
				'shouldMatch' => false,
			],
			// False positives — domain-only strings
			['input' => 'The domain sakoo.io does not contain an address.', 'shouldMatch' => false],
			['input' => 'Visit our website at www.example.com for more info.', 'shouldMatch' => false],

			// ── Phone ─────────────────────────────────────────────────────────
			[
				'input' => 'Call me at +98 912 345 6789.',
				'shouldMatch' => true, 'token' => '[PHONE]', 'original' => '+98 912 345 6789', 'reason' => 'phone number',
			],
			[
				'input' => 'My number is (021) 8765-4321.',
				'shouldMatch' => true, 'token' => '[PHONE]', 'original' => '(021) 8765-4321', 'reason' => 'phone number',
			],
			[
				'input' => 'Dial 09121234567 for customer support.',
				'shouldMatch' => true, 'token' => '[PHONE]', 'original' => '09121234567', 'reason' => 'phone number',
			],
			[
				'input' => 'US number: +1 800 555 0199',
				'shouldMatch' => true, 'token' => '[PHONE]', 'original' => '+1 800 555 0199', 'reason' => 'phone number',
			],
			// False positives — short codes and dates
			['input' => 'The release date is 2026-05-24.', 'shouldMatch' => false],
			['input' => 'ZIP code 12345 and model ID 9876.', 'shouldMatch' => false],
			['input' => 'Version 3.14159 of the library.', 'shouldMatch' => false],

			// ── Credit card ───────────────────────────────────────────────────
			[
				'input' => 'Charge card 4242 4242 4242 4242 for the subscription.',
				'shouldMatch' => true, 'token' => '[CARD]', 'original' => '4242 4242 4242 4242', 'reason' => 'credit card number',
			],
			[
				'input' => 'Card: 5500-0000-0000-0004',
				'shouldMatch' => true, 'token' => '[CARD]', 'original' => '5500-0000-0000-0004', 'reason' => 'credit card number',
			],
			[
				'input' => 'Amex: 378282246310005',
				'shouldMatch' => true, 'token' => '[CARD]', 'original' => '378282246310005', 'reason' => 'credit card number',
			],
			// False positives — fails Luhn
			['input' => 'Order ID is 1234567890123456 but it fails Luhn.', 'shouldMatch' => false],
			['input' => 'Transaction ref: 9999888877776666', 'shouldMatch' => false],

			// ── IBAN ──────────────────────────────────────────────────────────
			[
				'input' => 'Transfer to IR062960000000100324200001.',
				'shouldMatch' => true, 'token' => '[IBAN]', 'original' => 'IR062960000000100324200001', 'reason' => 'IBAN',
			],
			[
				'input' => 'IBAN: DE89370400440532013000',
				'shouldMatch' => true, 'token' => '[IBAN]', 'original' => 'DE89370400440532013000', 'reason' => 'IBAN',
			],
			[
				'input' => 'Bank details: GB29NWBK60161331926819',
				'shouldMatch' => true, 'token' => '[IBAN]', 'original' => 'GB29NWBK60161331926819', 'reason' => 'IBAN',
			],
			// False positives
			['input' => 'The ISO country code for Iran is IR.', 'shouldMatch' => false],

			// ── Iranian National ID ───────────────────────────────────────────
			// Repdigit NIDs (all same digit) must always be rejected
			['input' => 'NID 1111111111 is all ones and must be rejected.', 'shouldMatch' => false],
			['input' => 'Invalid NID: 2222222222', 'shouldMatch' => false],
			['input' => 'NID 9999999999 is all nines.', 'shouldMatch' => false],
			// Note: positive NID detection requires a checksum-valid 10-digit number.
			// Checksum validation is covered by the PiiMaskingDetector unit tests.

			// ── IPv4 ─────────────────────────────────────────────────────────
			[
				'input' => 'Server is at 192.168.1.100.',
				'shouldMatch' => true, 'token' => '[IP]', 'original' => '192.168.1.100', 'reason' => 'IPv4 address',
			],
			[
				'input' => 'Internal API is at 10.0.0.1:8080.',
				'shouldMatch' => true, 'token' => '[IP]', 'original' => '10.0.0.1', 'reason' => 'IPv4 address',
			],
			[
				'input' => 'External IP: 203.0.113.42',
				'shouldMatch' => true, 'token' => '[IP]', 'original' => '203.0.113.42', 'reason' => 'IPv4 address',
			],
			// False positives — version strings and dates
			['input' => 'Version 10.4.2 was released last week.', 'shouldMatch' => false],
			['input' => 'API v2.1.0 introduces breaking changes.', 'shouldMatch' => false],

			// ── Multiple PII types in one message ─────────────────────────────
			[
				'input' => 'Email user@test.com, phone +1 800 555 0199, card 4111 1111 1111 1111.',
				'shouldMatch' => true, 'token' => '[EMAIL]', 'original' => 'user@test.com', 'reason' => 'email address',
			],
			[
				'input' => 'Server 192.168.0.1, admin at root@internal.lan',
				'shouldMatch' => true, 'token' => '[IP]', 'original' => '192.168.0.1', 'reason' => 'IPv4 address',
			],

			// ── Clean messages ────────────────────────────────────────────────
			['input' => 'Please summarise the quarterly report.', 'shouldMatch' => false],
			['input' => 'ایران کشور زیبایی است.', 'shouldMatch' => false],
			['input' => 'What is the best framework for building APIs?', 'shouldMatch' => false],
			['input' => 'یک گزارش فنی بنویس.', 'shouldMatch' => false],
		]);
	}

	/** @phpstan-param array{input:string,shouldMatch:bool,token?:string,original?:string,reason?:string} $datasetItem */
	public function run(array $datasetItem): mixed
	{
		return $this->detector->detect((string) $datasetItem['input']);
	}

	/** @phpstan-param array{input:string,shouldMatch:bool,token?:string,original?:string,reason?:string} $datasetItem */
	public function evaluate(mixed $output, array $datasetItem): void
	{
		if (!$datasetItem['shouldMatch']) {
			$this->assert(new IsCleanResult(), $output);

			return;
		}

		$this->assert(new IsFlaggedAs(ContentClassification::Confidential), $output);
		$this->assert(new IsMaskedWith((string) ($datasetItem['token'] ?? ''), (string) ($datasetItem['original'] ?? '')), $output);
		$this->assert(new HasReason((string) ($datasetItem['reason'] ?? '')), $output);
	}
}
