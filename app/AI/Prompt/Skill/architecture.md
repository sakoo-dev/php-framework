# Sakoo Architecture Reference

## SOLID in Practice
- **SRP**: One reason to change per class. Split when a class serves two stakeholders.
- **OCP**: Extend via composition/decorators, not modification. Never edit closed code — wrap it.
- **LSP**: Subtypes must honor parent contracts — no surprises. Pre/post-conditions must hold.
- **ISP**: Split interfaces at 5–7 methods. Clients shouldn't depend on methods they don't call.
- **DIP**: Constructor injection only. Domain depends on abstractions, never on infrastructure.

## DDD Patterns
- **Entities**: Identity-based. Strongly-typed IDs (VOs wrapping UUID/int). Equality by identity.
- **Value Objects**: Immutable, validated in constructor, equality by value. `with*()` for mutations. Prefer empty VOs (`Money::zero()`) over null. Named constructors (`Money::fromCents(500)`) for clarity.
- **Aggregates**: Consistency boundary via root. Reference other aggregates by ID only. One transaction = one aggregate.
- **Repositories**: Interface in domain, implementation in infrastructure. Return domain objects, never raw arrays.
- **Specifications**: Composable rules (`AndSpecification`, `OrSpecification`). Keep query logic out of services.
- **Domain Events**: Immutable, past-tense names (`OrderPlaced`). Dispatched after state change, never before.
- **Domain Services**: Stateless. Used when logic spans multiple aggregates. Never inject infrastructure.
- **Anti-Corruption Layer**: Translate between Bounded Contexts. Never let external models leak into domain.

## Hexagonal Architecture
```
┌─────────────────────────────────────┐
│  Adapters (HTTP, CLI, MCP, Swoole)  │
│  ┌───────────────────────────────┐  │
│  │  Application (Handlers, Bus)  │  │
│  │  ┌─────────────────────────┐  │  │
│  │  │  Domain (Entities, VOs) │  │  │
│  │  └─────────────────────────┘  │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```
- Dependency rule: outer → inner only. Domain has zero framework/ORM/infra deps.
- Ports = interfaces defined in domain/application layer.
- Adapters = implementations in infrastructure layer.
- Application layer orchestrates use cases — no business logic here, only coordination.

## CQRS
- **Commands**: State-changing, return void or ID. Imperative names (`PlaceOrderCommand`). Validated before dispatch.
- **Queries**: Read-only, may bypass domain for read-optimized stores/projections.
- Dispatch via bus — never directly from controllers.
- Command handlers: one handler per command. No conditional routing.

## Error Handling
- Domain-specific exception hierarchies. Never catch `\Exception`/`\Throwable` except at app boundary.
- Use `Result`/`Either` for expected domain failures (insufficient funds, invalid state transitions).
- Exceptions for unexpected failures (DB down, file missing). Results for expected failures (validation, business rules).
- Never use exceptions for control flow.

## Security Checklist
- Validate/sanitize all input at adapter boundary. Never trust user input.
- Parameterized SQL only — never concatenate user input.
- `password_hash()` with `PASSWORD_ARGON2ID`; `hash_equals()` for token/HMAC comparison.
- Secure headers: CSP, X-Content-Type-Options, X-Frame-Options, HSTS.
- CSRF tokens on state-changing forms. Rate limiting on auth endpoints.
- No sensitive data in URLs or logs. Mask PII in error output.
- Principle of least privilege for file permissions and DB users.
- Validate file uploads: check MIME type, extension, size. Never trust client-reported type.

## Performance
- Cache hierarchy: HTTP reverse proxy → PSR-6 app cache → query cache → object cache.
- DB indexes on all FKs and filtered columns. Catch N+1 in code review.
- Paginate all list endpoints. Use generators/iterators for large datasets.
- Lazy-load expensive operations. Profile before optimizing — measure, don't guess.
- Connection pooling for DB and HTTP clients in Swoole context.
- Avoid `file_get_contents` for large files — use streaming reads.

## Testing Standards
- Domain logic → pure unit tests (no I/O, DB, HTTP, filesystem).
- Application handlers → integration tests, mocked infrastructure.
- Infrastructure adapters → integration tests against real dependencies.
- Naming: `PlaceOrderHandlerTest::it_throws_when_quantity_exceeds_stock`
- AAA pattern (Arrange/Act/Assert). One assertion concept per test.
- Object mothers or test data builders — no raw hardcoded arrays.
- Test behavior, not implementation. Refactoring should not break tests.
- `#[Test]` attribute + `snake_case` method names. No comments — tests must be self-readable.
- Separate stub/fake/spy classes into their own files.
