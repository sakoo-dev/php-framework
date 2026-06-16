<?php

declare(strict_types=1);

namespace App\AI\Neuron\Optimizer;

/**
 * Default prompt optimizer implementing token reduction strategies.
 */
final readonly class PromptOptimizer implements PromptOptimizerInterface
{
	public function optimize(string $text, ?int $maxLength = null): string
	{
		$text = $this->removeExtraWhitespace($text);
		$text = $this->aggregateRepetitions($text);
		$text = $this->removeUnnecessaryPhrases($text);

		if ($maxLength && strlen($text) > $maxLength) {
			$text = $this->truncate($text, $maxLength);
		}

		return $text;
	}

	private function removeExtraWhitespace(string $text): string
	{
		$text = (string) preg_replace('/[ \t]+/', ' ', $text);
		$text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

		return trim($text);
	}

	private function aggregateRepetitions(string $text): string
	{
		$lines = explode("\n", $text);
		$result = [];
		$previous = '';
		$count = 0;

		foreach ($lines as $line) {
			$trimmed = trim($line);

			if ($trimmed === $previous && '' !== $trimmed && $this->isRepetitionCandidate($trimmed)) {
				++$count;

				continue;
			}

			if ($count > 1) {
				$result[] = "{$previous} (x{$count})";
			} elseif ('' !== $previous) {
				$result[] = $previous;
			}

			$previous = $trimmed;
			$count = 1;
		}

		if ($count > 1) {
			$result[] = "{$previous} (x{$count})";
		} elseif ('' !== $previous) {
			$result[] = $previous;
		}

		return implode("\n", $result);
	}

	private function isRepetitionCandidate(string $line): bool
	{
		$candidates = ['PASS', 'FAIL', 'OK', 'ERROR', 'WARNING', '.', 'E', 'F', 'S'];

		return in_array($line, $candidates, true);
	}

	private function removeUnnecessaryPhrases(string $text): string
	{
		$patterns = [
			'/On branch \w+\n/',
			'/Your branch is up to date with .+?\n/',
			'/nothing to commit, working tree clean\n/',
		];

		foreach ($patterns as $pattern) {
			$text = (string) preg_replace($pattern, '', $text);
		}

		return $text;
	}

	private function truncate(string $text, int $maxLength): string
	{
		if (strlen($text) <= $maxLength) {
			return $text;
		}

		$truncated = substr($text, 0, $maxLength - 20);
		$lastNewline = strrpos($truncated, "\n");

		if (false !== $lastNewline) {
			$truncated = substr($truncated, 0, $lastNewline);
		}

		return $truncated . "\n\n[... truncated]";
	}
}
