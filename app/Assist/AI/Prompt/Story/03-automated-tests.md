# Unit Test Generation

## Paths
Source: `/var/www/html/core/src` · Tests: `/var/www/html/core/tests` · Coverage: `/var/www/html/storage/tests/coverage/`

## Goal
Raise coverage to ≥90% using coverage data as the single source of truth.

## Loop
1. Run tests → confirm green. Generate coverage: `make test-coverage` (HTML) or `composer test` (text).
2. Read coverage → find uncovered classes/methods.
3. Write/extend tests for meaningful uncovered logic. Skip anemic/DTO/formatter classes.
4. Repeat until ≥90%.

## Test Rules
- Never duplicate existing tests. Extend existing test files. Skip covered methods.
- Write each test immediately after selecting target — no batching.
- No comments — tests must be self-readable.
- Separate stub classes into their own files.

## Style
```php
final class SomeTest extends TestCase
{
    #[Test]
    public function readable_snake_case_name(): void
    {
        // Given
        // When
        // Then
    }
}
```
