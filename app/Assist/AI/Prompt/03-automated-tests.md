# Task: Generate Unit Tests for Sakoo PHP Framework

## Paths
- Source: `/var/www/html/core/src`
- Tests: `/var/www/html/core/tests`
- Coverage: `/var/www/html/storage/tests/coverage`

## Objective
Raise test coverage to ≥90% using coverage data as the single source of truth.

## Execution Plan
1. Run all tests → ensure they pass.
2. Generate coverage report:
    - HTML: `make test-coverage` OR `./vendor/bin/phpunit test --coverage-html=storage/tests/coverage/`
    - Text: `composer test` OR `./vendor/bin/phpunit --coverage-text`
3. Read coverage output → locate uncovered classes/methods.
4. Select ONLY meaningful uncovered logic (see Skip Rules).
5. Write or extend tests.
6. Repeat until coverage ≥90%.

## Tooling
- If file access fails → use MCP tools.
- Do NOT scan the entire codebase manually.

## Skip Rules
Ignore:
- Anemic classes (only getters/setters, no behavior)
- Formatter / DTO / data-only classes

## Test Rules
- Never duplicate existing tests.
- If test file exists → extend it.
- If a method is already covered → skip it.
- Write tests immediately after selecting a target (no batching).
- Don't Write any Comment, test should be Readable itself.
- Separate All Stub Classes into Separate Files.

## Test Style
```php
final class SomeTest extends TestCase
{
    #[Test]
    public function readable_snake_case_function_name(): void
    {
        // Given
        // When
        // Then
    }
}
```
