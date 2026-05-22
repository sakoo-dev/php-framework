# Sakoo Coding Conventions

## File Structure
- Every file: `<?php` + blank line + `declare(strict_types=1);`
- Full namespace matching directory structure (`PSR-4`).
- `use` block sorted: PHP stdlib → PSR interfaces → Sakoo Core → current module.
- One class/interface/enum per file. No multiple declarations.
- Complete `use` statements — no implicit global namespace for non-builtins.

## Type System
- Every param, property, and return explicitly typed. No untyped `mixed` without justification.
- Backed enums for fixed value sets. Pure enums for state machines.
- Readonly properties/classes for immutable data. Constructor property promotion for DI.
- Union/intersection types where needed. Nullsafe `?->` instead of null checks.
- `match` expression over `switch`. Named arguments for readability at call sites.
- `#[Attribute]` for metadata. Typed collections via `Set<T>` with PHPDoc generics.
- `never` return type for methods that always throw or exit.

## Comments & Documentation
- **No inline comments** — zero `//` or `/* */` comments inside method bodies or between statements. Express intent through naming and structure alone.
- **PHPDoc on classes and methods only** — write a description block based on functionality
- **Forbidden annotations**: `@param`, `@return`, `@var`, `@property`, `@method` — native type hints make these redundant. but you can use it to make recognizable for PHPStan.
- **Permitted annotations**: `@throws` (for documented exceptions) and PHPStan-specific annotations (`@phpstan-ignore`, `@phpstan-param`, `@phpstan-return`, `@phpstan-var`, etc.).
- PHPDoc for generic collections is permitted solely to carry type parameters (e.g. `@return list<Money>`).

## Code Style
- PHPStan max level must pass. PHPCSFixer project config must pass.
- 120-char soft line limit. 4-space indent (tabs in Sakoo convention).
- Method length: aim for <20 lines. Class length: aim for <200 lines.

## Naming
- Classes: `PascalCase`. Methods/properties: `camelCase`. Constants: `UPPER_SNAKE`.
- Interfaces: no `I` prefix — use descriptive names (`Repository`, not `IRepository`).
- Command names: imperative (`PlaceOrderCommand`). Event names: past-tense (`OrderPlaced`).
- Test methods: `snake_case` describing behavior (`it_throws_when_quantity_exceeds_stock`).
- Value Objects: noun-based (`Money`, `EmailAddress`, `OrderId`).

## Dependency Injection
- Constructor injection only — no `$container->get()` in domain/application code.
- No `static` methods in domain/application (except VO named constructors like `Money::zero()`).
- No superglobals outside PSR-7 adapter code.
- No `new \DateTime()` or `time()` — inject `ClockInterface`.
- No hardcoded paths — use `Path::` static helpers.
- No `date()`/`time()`/`new \DateTime` in domain — inject `Psr\Clock\ClockInterface`.

## Immutability
- Value Objects immutable with `with*()` methods returning new instances.
- DTOs `readonly`. Events `readonly`.
- Prefer empty VOs (`Money::zero()`, `Set::empty()`) over `null`.
- PSR-7 objects are immutable — use `with*()` methods.
- PSR-14 events immutable with past-tense names.

## Error Handling
- Domain-specific exception hierarchies. Never catch `\Exception`/`\Throwable` except at app boundary.
- Use `throwIf()` / `throwUnless()` instead of bare `if (...) throw` where available.
- `Result`/`Either` pattern for expected domain failures.
- Log structured context — never log PII.

## PSR Compliance
- **PSR-1**: Basic coding standard. One class per file.
- **PSR-3**: `LoggerInterface`. Structured context. Never log PII.
- **PSR-4**: Autoloading. Namespace matches directory.
- **PSR-6**: Caching interface. 
- **PSR-7**: Immutable HTTP messages. Use `with*()` methods.
- **PSR-11**: Container. Constructor injection only.
- **PSR-14**: Event dispatcher. Immutable events, past-tense names.
- **PSR-15**: HTTP middleware. Single responsibility per middleware.
- **PSR-17**: HTTP factories.
- **PSR-18**: HTTP client. Wrap `ClientInterface` in domain gateway. Handle all three exception types (`ClientExceptionInterface`, `NetworkExceptionInterface`, `RequestExceptionInterface`).

## Code Generation Rules
1. `<?php` + `declare(strict_types=1);` in every file.
2. Full namespace declarations matching directory structure.
3. Complete `use` statements — no implicit global namespace for non-builtins.
4. Complete, runnable code — no `// ...` placeholders unless labeled partial.
5. No `static` methods in domain/application (except VO named constructors).
6. No superglobals outside PSR-7 adapter code.
7. No `date()`/`time()`/`new \DateTime()` in domain — inject `ClockInterface`.
8. PHPCSFixer project config must pass. PHPStan max level must be applied.
