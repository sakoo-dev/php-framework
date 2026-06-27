<?php

declare(strict_types=1);

namespace System\AI;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sakoo\Framework\Core\Http\Client\HttpClient;
use Sakoo\Framework\Core\Http\Client\HttpClientException;

/**
 * Adapts sakoo/core's HttpClient to PSR-18 ClientInterface.
 *
 * sakoo's HttpClientInterface uses send(), PSR-18 uses sendRequest().
 * This thin wrapper bridges the two so McpWebClient can stay PSR-18 clean
 * while still running on the sakoo HTTP stack in this application.
 *
 * When shipping app/AI as a standalone package in other frameworks (Laravel,
 * Symfony), pass their native PSR-18 client directly — no bridge needed.
 */
final class HttpClientBridge implements ClientInterface
{
	public function __construct(private readonly HttpClient $inner) {}

	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		try {
			return $this->inner->send($request);
		} catch (HttpClientException $e) {
			throw new class($e->getMessage(), $e->getCode(), $e) extends \RuntimeException implements ClientExceptionInterface {};
		}
	}
}
