# Quality Assurance & Verification Reference

## Code Review Checklist

### Architecture
- [ ] Correct layer placement (Domain/Application/Infrastructure).
- [ ] No dependency rule violations (inner layer never imports outer).
- [ ] Aggregate boundaries respected (cross-aggregate by ID only).
- [ ] No service locator or hidden dependencies.

### Type Safety
- [ ] Every param/property/return explicitly typed.
- [ ] No `mixed` without justification.
- [ ] Backed enums for fixed sets. Union types where appropriate.
- [ ] Readonly properties for immutability.

### PSR Compliance
- [ ] PSR-4 autoloading (namespace matches directory).
- [ ] PSR-7 immutable HTTP objects (no mutation of request/response).
- [ ] PSR-11 constructor injection (no `$container->get()` in domain).
- [ ] PSR-14 events are immutable, past-tense named.
- [ ] PSR-15 middleware pipeline used for cross-cutting concerns.
- [ ] PSR-18 client wrapped in domain gateway. All three exception types handled.

### Error Handling
- [ ] Domain-specific exception hierarchy.
- [ ] No bare `catch (\Exception)` except at app boundary.
- [ ] Context included in exceptions (IDs, requested vs available).
- [ ] `Result`/`Either` for expected domain failures.

### Security
- [ ] All input validated at adapter boundary.
- [ ] SQL parameterized (no concatenation).
- [ ] No PII in logs.
- [ ] CSRF tokens on state-changing forms.
- [ ] Secrets in env vars, not code.

### Performance
- [ ] No N+1 queries.
- [ ] Indexes on FKs and filtered columns.
- [ ] Pagination on list endpoints.
- [ ] Generators for large datasets.

## Test Writing Process
1. Run existing tests → ensure green.
2. Generate coverage report (`make test-coverage` or `composer test`).
3. Read coverage → identify uncovered classes/methods.
4. Skip anemic/DTO/formatter classes.
5. Write test immediately after selecting target — no batching.
6. Repeat until ≥90% coverage.

### Test Style
```php
final class SomeTest extends TestCase
{
    #[Test]
    public function readable_snake_case_name(): void
    {
        // Given (Arrange)
        // When (Act)
        // Then (Assert)
    }
}
```

### Test Rules
- Never duplicate existing tests. Extend existing test files.
- No comments — tests must be self-readable.
- Separate stub classes into their own files.
- Object mothers or test data builders — no raw hardcoded arrays.
- One assertion concept per test.
- Domain tests: pure unit (no I/O). Handler tests: integration with mocked infra.
