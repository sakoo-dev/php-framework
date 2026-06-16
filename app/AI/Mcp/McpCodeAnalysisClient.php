<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use System\Path\Path;

/**
 * Code analysis and navigation for MCP tools.
 *
 * Provides find_references, find_definition, and code_metrics operations
 * using token-based parsing and regex matching.
 */
final class McpCodeAnalysisClient
{
	private const int MAX_REFERENCES = 500;

	/**
	 * Finds all references to a class, method, or function name.
	 *
	 * @return array{symbol: string, references: array<int, array{file: string, line: int, context: string}>, total: int, truncated: bool}
	 */
	public function findReferences(string $symbol, string $path = ''): array
	{
		$searchPath = !$path ? (string) Path::getAppDir() : $path;

		if (!is_dir($searchPath)) {
			$searchPath = dirname($searchPath);
		}

		$references = [];
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($searchPath, \FilesystemIterator::SKIP_DOTS)
		);

		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile() || 'php' !== $file->getExtension()) {
				continue;
			}

			$filePath = $file->getPathname();

			if ($this->shouldSkipFile($filePath)) {
				continue;
			}

			$lines = @file($filePath);

			if (false === $lines) {
				continue;
			}

			foreach ($lines as $lineNo => $lineText) {
				if (preg_match('/\b' . preg_quote($symbol, '/') . '\b/', $lineText)) {
					$references[] = [
						'file' => $this->relativePath($filePath),
						'line' => $lineNo + 1,
						'context' => trim($lineText),
					];

					if (count($references) >= self::MAX_REFERENCES) {
						break 2;
					}
				}
			}
		}

		return [
			'symbol' => $symbol,
			'references' => $references,
			'total' => count($references),
			'truncated' => count($references) >= self::MAX_REFERENCES,
		];
	}

	/**
	 * Finds the definition of a class, method, or function.
	 *
	 * @return array{symbol: string, definition: null|array{file: string, line: int, context: string}}
	 */
	public function findDefinition(string $symbol, string $path = ''): array
	{
		$searchPath = '' === $path ? (string) Path::getAppDir() : $path;

		if (!is_dir($searchPath)) {
			$searchPath = dirname($searchPath);
		}

		$patterns = [
			'/^(class|interface|trait|enum)\s+' . preg_quote($symbol, '/') . '\b/',
			'/^(public|protected|private|static)?\s*(function)\s+' . preg_quote($symbol, '/') . '\s*\(/',
			'/^(final\s+)?(class|interface|trait|enum)\s+' . preg_quote($symbol, '/') . '\b/',
		];

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($searchPath, \FilesystemIterator::SKIP_DOTS)
		);

		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile() || 'php' !== $file->getExtension()) {
				continue;
			}

			$filePath = $file->getPathname();

			if ($this->shouldSkipFile($filePath)) {
				continue;
			}

			$lines = @file($filePath);

			if (false === $lines) {
				continue;
			}

			foreach ($lines as $lineNo => $lineText) {
				foreach ($patterns as $pattern) {
					if (preg_match($pattern, trim($lineText))) {
						return [
							'symbol' => $symbol,
							'definition' => [
								'file' => $this->relativePath($filePath),
								'line' => $lineNo + 1,
								'context' => trim($lineText),
							],
						];
					}
				}
			}
		}

		return ['symbol' => $symbol, 'definition' => null];
	}

	/**
	 * Calculates code metrics for a file or directory.
	 *
	 * @return array{path: string, files: int, lines: int, classes: int, methods: int, functions: int}
	 */
	public function codeMetrics(string $path): array
	{
		if (!is_dir($path)) {
			return $this->fileMetrics($path);
		}

		$totalFiles = 0;
		$totalLines = 0;
		$totalClasses = 0;
		$totalMethods = 0;
		$totalFunctions = 0;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
		);

		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			if (!$file->isFile() || 'php' !== $file->getExtension()) {
				continue;
			}

			$filePath = $file->getPathname();

			if ($this->shouldSkipFile($filePath)) {
				continue;
			}

			$metrics = $this->fileMetrics($filePath);
			++$totalFiles;
			$totalLines += $metrics['lines'];
			$totalClasses += $metrics['classes'];
			$totalMethods += $metrics['methods'];
			$totalFunctions += $metrics['functions'];
		}

		return [
			'path' => $this->relativePath($path),
			'files' => $totalFiles,
			'lines' => $totalLines,
			'classes' => $totalClasses,
			'methods' => $totalMethods,
			'functions' => $totalFunctions,
		];
	}

	/**
	 * Calculates metrics for a single file.
	 *
	 * @return array{path: string, files: int, lines: int, classes: int, methods: int, functions: int}
	 */
	private function fileMetrics(string $path): array
	{
		$lines = @file($path);

		if (false === $lines) {
			return [
				'path' => $this->relativePath($path),
				'files' => 0,
				'lines' => 0,
				'classes' => 0,
				'methods' => 0,
				'functions' => 0,
			];
		}

		$content = implode('', $lines);
		$classes = preg_match_all('/(class|interface|trait|enum)\s+\w+/', $content) ?: 0;
		$methods = preg_match_all('/function\s+\w+\s*\(/', $content) ?: 0;
		$functions = preg_match_all('/^function\s+\w+\s*\(/m', $content) ?: 0;

		return [
			'path' => $this->relativePath($path),
			'files' => 1,
			'lines' => count($lines),
			'classes' => $classes,
			'methods' => $methods,
			'functions' => $functions,
		];
	}

	private function shouldSkipFile(string $path): bool
	{
		$skipDirs = ['vendor', 'node_modules', '.git', 'storage', '.idea'];

		foreach ($skipDirs as $dir) {
			if (str_contains($path, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR)) {
				return true;
			}
		}

		return false;
	}

	private function relativePath(string $absolutePath): string
	{
		$root = rtrim((string) Path::getRootDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		if (str_starts_with($absolutePath, $root)) {
			return substr($absolutePath, strlen($root));
		}

		return $absolutePath;
	}
}
