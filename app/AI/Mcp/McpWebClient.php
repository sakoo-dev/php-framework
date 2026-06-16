<?php

declare(strict_types=1);

namespace App\AI\Mcp;

use App\AI\Mcp\Web\Exception\WebFetchException;
use App\AI\Mcp\Web\Exception\WebSearchException;
use Sakoo\Framework\Core\Env\Env;
use Sakoo\Framework\Core\Http\Client\HttpClient;
use Sakoo\Framework\Core\Http\Client\HttpClientException;

/**
 * Unified HTTP client for web operations (fetch and search).
 *
 * Provides two core capabilities:
 *
 * 1. **fetch()** — Fetches a URL and returns the raw response body with metadata
 *    (status code, headers). Follows redirects by default and enforces a
 *    configurable timeout to prevent hanging on slow or unresponsive hosts.
 *
 * 2. **search()** — Queries the Brave Web Search API and returns up to $count
 *    structured results (title, url, description). Requires the environment
 *    variable BRAVE_SEARCH_API_KEY to be set.
 *
 * Both methods share a common HttpClient instance and consistent error handling
 * patterns. Responses are capped at 5MB for fetch() to prevent memory exhaustion,
 * and search() enforces a maximum of 20 results.
 *
 * API reference: https://api.search.brave.com/app/documentation/web-search
 *
 * @see WebFetchException Thrown when HTTP fetch fails.
 * @see WebSearchException Thrown when API key is missing or search fails.
 */
final class McpWebClient
{
	private const string BRAVE_API_URL = 'https://api.search.brave.com/res/v1/web/search';
	private const int MAX_SEARCH_COUNT = 20;
	private const int MAX_BODY_SIZE = 5_242_880;
	private const float DEFAULT_FETCH_TIMEOUT = 15.0;
	private const float DEFAULT_SEARCH_TIMEOUT = 10.0;

	public function __construct(private readonly HttpClient $http) {}

	/**
	 * Fetches the given URL and returns the response body with metadata.
	 *
	 * @return array{url: string, statusCode: int, headers: array<string, array<string>>, body: string, truncated: bool}
	 *
	 * @throws WebFetchException when the HTTP call fails
	 */
	public function fetch(string $url, float $timeout = self::DEFAULT_FETCH_TIMEOUT): array
	{
		try {
			$response = $this->http
				->withTimeout($timeout)
				->withHeader('User-Agent', 'Sakoo-MCP/1.0')
				->get($url);
		} catch (HttpClientException $e) {
			throw new WebFetchException("HTTP fetch failed for {$url}: {$e->getMessage()}", previous: $e);
		}

		$body = (string) $response->getBody();
		$truncated = false;

		if (strlen($body) > self::MAX_BODY_SIZE) {
			$body = substr($body, 0, self::MAX_BODY_SIZE);
			$truncated = true;
		}

		return [
			'url' => $url,
			'statusCode' => $response->getStatusCode(),
			'headers' => $this->normalizeHeaders($response->getHeaders()),
			'body' => $body,
			'truncated' => $truncated,
		];
	}

	/**
	 * Searches the web via Brave Search API and returns structured results.
	 *
	 * @return array{query: string, results: array<int, array{title: string, url: string, description: string}>}
	 *
	 * @throws WebSearchException when the API key is absent or the HTTP call fails
	 */
	public function search(string $query, int $count = 5): array
	{
		$apiKey = Env::get('BRAVE_SEARCH_API_KEY', '');

		if (!is_string($apiKey) || '' === $apiKey) {
			throw new WebSearchException('BRAVE_SEARCH_API_KEY environment variable is not set.');
		}

		$count = max(1, min($count, self::MAX_SEARCH_COUNT));
		$url = self::BRAVE_API_URL . '?' . http_build_query([
			'q' => $query,
			'count' => $count,
			'text_decorations' => 0,
		]);

		try {
			$response = $this->http
				->withTimeout(self::DEFAULT_SEARCH_TIMEOUT)
				->withHeader('Accept', 'application/json')
				->withHeader('Accept-Encoding', 'gzip')
				->withHeader('X-Subscription-Token', $apiKey)
				->get($url);
		} catch (HttpClientException $e) {
			throw new WebSearchException("Brave Search HTTP request failed: {$e->getMessage()}", previous: $e);
		}

		$statusCode = $response->getStatusCode();

		if (200 !== $statusCode) {
			throw new WebSearchException("Brave Search API returned HTTP {$statusCode}.");
		}

		$body = (string) $response->getBody();

		/** @var null|array<mixed, mixed> $decoded */
		$decoded = json_decode($body, true);

		if (!is_array($decoded)) {
			throw new WebSearchException('Brave Search API returned non-JSON response.');
		}

		return [
			'query' => $query,
			'results' => $this->extractSearchResults($decoded),
		];
	}

	/**
	 * Normalizes HTTP headers to a consistent array<string, array<string>> structure.
	 *
	 * @param array<mixed, mixed> $headers
	 *
	 * @return array<string, array<string>>
	 */
	private function normalizeHeaders(array $headers): array
	{
		$normalized = [];

		foreach ($headers as $name => $values) {
			if (!is_string($name)) {
				continue;
			}

			$stringValues = [];

			foreach ((array) $values as $value) {
				if (is_string($value)) {
					$stringValues[] = $value;
				} elseif (is_scalar($value)) {
					$stringValues[] = (string) $value;
				}
			}

			$normalized[$name] = $stringValues;
		}

		return $normalized;
	}

	/**
	 * Extracts the normalized result list from the raw Brave API response payload.
	 *
	 * @param array<mixed, mixed> $decoded
	 *
	 * @return array<int, array{title: string, url: string, description: string}>
	 */
	private function extractSearchResults(array $decoded): array
	{
		$web = $decoded['web'] ?? null;

		if (!is_array($web)) {
			return [];
		}

		$webResults = $web['results'] ?? [];

		if (!is_array($webResults)) {
			return [];
		}

		$results = [];

		foreach ($webResults as $item) {
			if (!is_array($item)) {
				continue;
			}

			$title = $item['title'] ?? '';
			$url = $item['url'] ?? '';
			$description = $item['description'] ?? '';

			$results[] = [
				'title' => is_string($title) ? $title : '',
				'url' => is_string($url) ? $url : '',
				'description' => is_string($description) ? $description : '',
			];
		}

		return $results;
	}
}
