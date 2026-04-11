# HTTP Foundation

Build the HTTP module at `core/src/Http/` — the bridge between the transport layer (Swoole / PHP-FPM) and the Sakoo Kernel.

## Scope
- PSR-7 value objects (Request, Response, Uri, Stream, UploadedFile, Headers)
- PSR-15 middleware pipeline with typed middleware stack
- PSR-17 HTTP factories
- Typed route registry with method+pattern→handler mapping
- Transport abstraction: Swoole and PHP-FPM behind a shared interface, swappable via ServiceLoader
- Response emission back to the active transport
- Convenience wrappers: HttpRequest, HttpResponse, Controller, Middleware base classes
- Benchmark infrastructure for concurrency and performance testing
- Global middleware registration via Open/Closed principle

## Architecture

```
Adapter (Swoole / FPM)
  ↓ creates PSR-7 Request via Factory
Transport Interface ← SwooleTransport | FpmTransport
  ↓
Middleware Pipeline (PSR-15)
  ↓
Router → Controller (single-action or multi-action)
  ↓
HttpResponse (fluent builder)
  ↓
PSR-7 Response
  ↓
ResponseEmitter (transport-aware)
```

### Transport Abstraction
Define contracts in `Http/Transport/`:
- `TransportRequest` — wraps Swoole\Http\Request or $_SERVER+php://input behind one interface.
- `ResponseEmitter` — sends a PSR-7 Response through the active transport.

Concrete implementations live in `Http/Transport/Swoole/` and `Http/Transport/Fpm/`. ServiceLoader binds the correct pair based on Kernel mode or env config — application code never imports Swoole or FPM directly.

### Convenience Wrappers

#### HttpRequest (`core/src/Http/HttpRequest.php`)
`final readonly` wrapper around `ServerRequestInterface`. Provides typed accessors: `method()`, `path()`, `input()`, `query()`, `header()`, `bearerToken()`, `cookie()`, `routeParam()`, `file()`, `json()`, `ip()`, `isSecure()`, `isJson()`, etc. Immutable — `withPsr()` and `withAttribute()` return new instances.

#### HttpResponse (`core/src/Http/HttpResponse.php`)
Fluent mutable builder wrapping immutable PSR-7 Response. Static factories: `json()`, `text()`, `html()`, `redirect()`, `noContent()`, `created()`, `fromPsr()`. Mutators: `withStatus()`, `withHeader()`, `withBody()`, `withCookie()`, `withCacheControl()`. Reader: `status()`, `header()`, `hasHeader()`. Output: `toPsrResponse()`.

#### Controller (`core/src/Http/Controller.php`)
Abstract base providing response helpers (`json()`, `text()`, `html()`, `redirect()`, `noContent()`, `created()`). Two patterns:
- **Single-action**: override `__invoke(HttpRequest): HttpResponse`, register as `$router->get('/path', MyController::class)`
- **Multi-action**: define named methods, register as `$router->get('/path', [MyController::class, 'action'])`

The Router calls `callAction()` for multi-action dispatch. `handle()` bridges PSR-15 for single-action.

#### Middleware (`core/src/Http/Middleware/Middleware.php`)
Abstract base bridging PSR-15 `MiddlewareInterface` to Sakoo layer. Subclasses implement `handle(HttpRequest, Closure): HttpResponse`. The `$next` closure signature: `fn(HttpRequest): HttpResponse`. Short-circuit by returning without calling `$next`.

### Route Definition
Each app module defines routes in `app/{Module}/routes.php`, returning a closure that receives the Router:
```php
return function (Router $router): void {
    // Single-action (invokable)
    $router->get('/health', HealthController::class);

    // Multi-action (named methods)
    $router->get('/users', [UserController::class, 'index']);
    $router->get('/users/{id}', [UserController::class, 'show']);
    $router->post('/users', [UserController::class, 'store']);
};
```
Router auto-discovers these files from registered modules. Middleware attaches per-route or per-group.

### Route Pattern Matching
`Route::buildPattern()` converts URI patterns to `Regex` instances using the framework's fluent regex builder. Named placeholders `{name}` become named capture groups. Trailing slashes are normalised — `/metrics` and `/metrics/` match the same route. Root `/` is preserved.

### Global Middleware
The global middleware stack is declared in `system/Middleware/Middlewares.php` — a flat PHP file returning an array of class-string. Adding or removing middleware only touches this file (Open/Closed principle). The entry point (`public/swoole.php`) requires this file and passes the array to `MiddlewarePipeline`.

### Profiler Integration
The `Kernel->Profiler` (singleton, process-scoped) handles:
- **Named timers**: `start($key)` / `elapsedTime($key)` for millisecond-precision timing via ClockInterface.
- **High-resolution timing**: `hrtimeNs()` for sub-millisecond measurements without DateTimeImmutable overhead.
- **Concurrency tracking**: `requestStarted()` / `requestFinished()` / `activeRequests()` / `peakRequests()` / `totalRequests()` — process-level atomic counters safe under Swoole's cooperative model.

No per-request profiler classes — all state lives in the singleton Profiler to avoid memory leaks and key collisions.

### Benchmark Infrastructure
- `BenchmarkResult` (readonly VO): parsed from Apache Benchmark (`ab`) output. Fields: timestamp, target, concurrency, rps, latency percentiles (p50/p90/p95/p99), failures, sapi.
- `bin/benchmark`: shell script that runs `ab`, parses output, stores results as JSONL in `storage/benchmark/results.jsonl` using the framework `File` class (no dedicated store class).
- `GET /metrics`: single endpoint returning live runtime stats + stored benchmark data.
- Makefile targets: `benchmark` (c=100,n=1000), `benchmark-heavy` (c=500,n=5000), `benchmark-ladder` (10→50→100→200→500), `benchmark-clear`.

### Scope Boundaries (Shared vs Per-Request)

**Shared scope** (process-level singletons, live for entire process lifetime):
- Kernel, Container, Profiler, Router — created once at boot
- Profiler concurrency counters — accumulate across all requests in the worker
- Router route table — populated at boot, read-only during request handling

**User scope** (per-request, ephemeral, garbage-collected after request):
- HttpRequest, HttpResponse — created fresh per request, no shared state
- Controller instances — resolved from container per request
- Request attributes, parsed body, query params — isolated per request
- Middleware `$next` closure — captures its own pipeline index per dispatch

Under Swoole, all coroutines share one heap. Memory metrics are process-level, not per-request. Headers are named `X-Process-Memory-*` to make this explicit.

### Response Headers
The `ProfilerMiddleware` attaches diagnostic headers to every response:
- `X-Response-Time-Ms` / `X-Response-Time-Us` — hrtime-based wall-clock timing
- `X-Process-Memory-Kb` / `X-Process-Memory-Peak-Kb` — process-level memory
- `X-Concurrent-Requests` / `X-Concurrent-Peak` / `X-Total-Requests` — from Profiler
- `X-Runtime-Sapi` — actual PHP SAPI (`cli` = Swoole, `fpm-fcgi` = FPM)
- `X-Worker-Pid` — worker process ID for correlation
- `X-Powered-By` — framework identity for Wappalyzer detection (`Sakoo Framework`)
- `X-Request-Id` — unique 32-char hex ID (or preserved from upstream)

### Security Headers (XssProtectionMiddleware)
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Content-Security-Policy: default-src 'self'`

## Entry Points
- `public/index.php` — boots Kernel, requires `swoole.php`.
- `public/kernel.php` — loads .env, boots Kernel in HTTP mode.
- `public/swoole.php` — loads routes, loads global middleware from `system/Middleware/Middlewares.php`, starts Swoole server on port 9501 (configurable via `SWOOLE_PORT` env var).

## Namespace
`Sakoo\Framework\Core\Http`

## Constraints
- Zero third-party HTTP libraries — implement PSR interfaces directly.
- Domain code depends on PSR interfaces, never on transport implementations.
- All PSR-7 objects immutable — `with*()` mutators return new instances.
- Validate/sanitize input at adapter boundary (transport layer), not in domain.
- Middleware: single responsibility per class. Cross-cutting only. No business logic.
- Constructor injection only — no service locator, no superglobals outside transport adapters.
- No per-request classes for timing/concurrency — use the singleton Profiler.
- Benchmark data stored as JSONL via the File class — no dedicated store classes.
- Route patterns use the Regex class — no raw regex strings in Route.
- Global middleware list is a separate file — no hardcoded arrays in entry points.

## Steps
1. Install PSR contract packages via Composer.
2. Implement PSR-7 value objects (Request, Response, Uri, Stream, Headers, UploadedFile).
3. Implement PSR-17 factories.
4. Build transport abstraction layer (contracts + Swoole + FPM implementations).
5. Build PSR-15 middleware dispatcher.
6. Build Router (typed route registry, Regex-based pattern matching, handler resolution via Container).
7. Build ResponseEmitter per transport.
8. Build convenience wrappers: HttpRequest, HttpResponse, Controller, Middleware base classes.
9. Build system middleware: ProfilerMiddleware, RequestIdMiddleware, XssProtectionMiddleware.
10. Build MetricsController with benchmark integration.
11. Create `HttpServiceLoader` — register all bindings; wire into `Loaders.php`.
12. Create entry points (`public/index.php`, `public/kernel.php`, `public/swoole.php`).
13. Create `system/Middleware/Middlewares.php` for global middleware registration.
14. Create `bin/benchmark` and Makefile targets for Apache Benchmark.
15. Write tests per component. Include scope boundary tests (shared vs user-scoped).
16. Document class purpose and public method behavior.

## Documentation Rules
- No `[at-sign]return` or `[at-sign]param` annotations — use native types.
- Only `[at-sign]throws` allowed from the annotation set.
- Class-level PHPDoc: purpose + key behavioral notes. Method-level: behavior summary only.

## Test Expectations
- PSR-7 objects: immutability, `with*()` returns new instance, header case-insensitivity.
- Router: method matching, pattern params, trailing slash normalization, 404/405 scenarios.
- Middleware pipeline: ordering, short-circuit, exception propagation.
- Transport adapters: request/response translation fidelity.
- Controller: single-action dispatch, multi-action dispatch, response helpers.
- Scope boundaries: Profiler is shared singleton; HttpRequest/HttpResponse are per-request isolated; middleware does not leak state between requests; concurrent requests see own query params.
- Benchmark: BenchmarkResult round-trip serialization.
- Style: `#[Test]` + `snake_case` method names, AAA pattern, no comments.
