# HTTP Optimization

Extend the HTTP module with an outbound `HttpClient`.

## Scope
`HttpClient`: PSR-compliant outbound HTTP client backed by Swoole's `Coroutine\Http\Client` (wrapped, not subclassed); falls back to `stream_context_create` under FPM. Registered via `HttpServiceLoader`.

### Swoole native check

`Swoole\Coroutine\Http\Client` exists in Swoole ≥ 4.4. Wrap it — do not subclass. Under FPM (`php_sapi_name() !== 'cli'`), fall back to a `StreamHttpDriver` that uses `stream_context_create` + `file_get_contents`.

### Contracts — `core/src/Http/Client/`

```
HttpClientInterface
  send(RequestInterface $request): ResponseInterface

HttpDriverInterface (internal)
  send(RequestInterface $request): ResponseInterface
```

### Class: `HttpClient` — `core/src/Http/Client/HttpClient.php`

```php
final class HttpClient implements HttpClientInterface
{
    public function __construct(private readonly HttpDriverInterface $driver) {}

    public function send(RequestInterface $request): ResponseInterface { … }
    public function get(string $uri, array $headers = []): ResponseInterface { … }
    public function post(string $uri, string $body, array $headers = []): ResponseInterface { … }
    public function put(string $uri, string $body, array $headers = []): ResponseInterface { … }
    public function patch(string $uri, string $body, array $headers = []): ResponseInterface { … }
    public function delete(string $uri, array $headers = []): ResponseInterface { … }
    public function withTimeout(float $seconds): static { … }  // returns new instance
    public function withHeader(string $name, string $value): static { … }
}
```

Convenience methods build a PSR-7 `Request` via `HttpFactory` and delegate to `$this->driver->send()`.

`withTimeout` and `withHeader` return new instances (immutable fluent API).

### `SwooleHttpDriver` — `core/src/Http/Client/SwooleHttpDriver.php`

```
- Wraps Swoole\Coroutine\Http\Client
- Parses URI to extract host / port / path
- Sets headers via $client->setHeaders()
- Calls $client->execute($path)
- Reads $client->statusCode, $client->headers, $client->body
- Builds a PSR-7 Response via HttpFactory
- Throws HttpClientException on connection failure (statusCode === -1)
```

### `StreamHttpDriver` — `core/src/Http/Client/StreamHttpDriver.php`

```
- Uses stream_context_create with 'http' context
- Supports all methods via context 'method' key
- Reads $http_response_header after file_get_contents
- Parses status line from first header
- Builds a PSR-7 Response via HttpFactory
- Throws HttpClientException on false return
```

### `HttpClientException` — `core/src/Http/Client/HttpClientException.php`

Extends `\RuntimeException`. Carries the original `RequestInterface` for debugging.

### Registration in `HttpServiceLoader`

```php
$this->container->bind(HttpDriverInterface::class, function (): HttpDriverInterface {
    return PHP_SAPI === 'cli'
        ? new SwooleHttpDriver()
        : new StreamHttpDriver();
});

$this->container->bind(HttpClientInterface::class, HttpClient::class);
```

## Namespace
All new classes under `Sakoo\Framework\Core\Http\*` following the existing module conventions.

## Constraints
- `HttpClient` is immutable; `withTimeout`/`withHeader` return new instances.
- `SwooleHttpDriver` must not be imported in any FPM-only path — resolve via `HttpDriverInterface`.

## Steps
6. Wire `HttpClient` bindings in `HttpServiceLoader`.
7. Tests: HttpClient driver swap.

## Test Expectations
- `HttpClient::withTimeout` returns a new instance, does not mutate the original.
- `HttpClient::withHeader` returns a new instance.
- `SwooleHttpDriver` is bound when SAPI is `cli`; `StreamHttpDriver` when `fpm-fcgi`.
