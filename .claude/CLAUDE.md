# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

All commands run inside Docker via `./sakoo` or `make`. Direct `composer` equivalents work locally if PHP is available.

```bash
make                        # First-time setup: permissions, git hooks, symlink, .env, docker up, composer install
make up / make down         # Start/stop Docker containers
make lint                   # Auto-fix code style (PHP CS Fixer)
make test                   # Run PHPUnit tests
make test-coverage          # Generate HTML coverage → storage/tests/coverage/
make analyse                # PHPStan static analysis (level max / PHP 8.4)
make check                  # Full CI: lint + test + analyse + validate + audit + docker build + doc + coverage
make doc                    # Generate API documentation
make shell                  # Open shell in app container
```

Running a single test file or suite:
```bash
./sakoo test -- --filter=TestClassName
./sakoo test -- path/to/Tests/SomeTest.php
```

Composer scripts (run inside Docker or directly):
```bash
./sakoo composer lint       # Check only (no auto-fix)
./sakoo composer test
./sakoo composer analyse
```

## Architecture

**Two independent namespaces ship in this repo:**

| Path | Composer package | Namespace |
|------|-----------------|-----------|
| `core/` | `sakoo/framework-core` | `Sakoo\Framework\Core\*` |
| `app/` | (application) | `App\*` |
| `system/` | (application) | `System\*` |

### `core/` — Framework Core Library

Zero third-party runtime dependencies (only PSR interfaces). Each subdirectory of `core/src/` is a self-contained module:

- **Container** — PSR-11 DI container with optional file-based cache (`ShouldCache` interface)
- **Kernel** — Bootstrap lifecycle: starts `Container`, loads `ServiceLoader`, sets error/exception handlers, dispatches by `Mode` (Console / HTTP / Test)
- **Console** — CLI application: `Application`, `Command`, `Input`, `Output`, interactive `RadioButton` component
- **ServiceLoader** — Registers bindings into the container; `MainLoader` is the app-level loader
- **FileSystem** — `Disk` / `Storage` / `File` / `Permission` over a `Local` adapter
- **Logger** — PSR-3 file logger with `LogFormatter`
- **Assert** — Fluent assertion chains (`Assert::that()`, `Assert::lazy()`)
- **Set** — Generic typed collection with search/sort strategies
- **VarDump** — `dd()`-style dumper with separate CLI and HTTP formatters
- **Clock** — PSR-20 clock with test-mode freeze support
- **Finder** — File finder with `.gitignore` awareness
- **Profiler**, **Regex**, **Str**, **Path**, **Env**, **Locker** — Single-purpose utilities

### `app/` — Application Modules

Domain-driven (not MVC). Each module owns its own `Tests/` directory.

- **Assist** — AI tooling hub:
  - `AI/Mcp/` — MCP (Model Context Protocol) server; exposes framework tools to AI assistants via `bin/mcp`
  - `AI/Prompt/` — Prompt files used by the MCP server
  - `Commands/` — Console commands registered in `assist`: `agent`, `chatbot`, `mcp:run`, `example`, etc.

### Entry Points

| File | Purpose |
|------|---------|
| `assist` | CLI entry point → boots Kernel (Mode::Console) → runs registered commands |
| `public/index.php` | HTTP entry point (served by Nginx) |
| `bin/sakoo` | Docker proxy wrapper — routes `php`, `assist`, `composer`, `test`, `npm`, `mysql`, `shell` into the container |
| `bin/mcp` | Starts the MCP server via Docker exec |

### Bootstrap Flow

```
assist / public/index.php
  └─ Kernel::start(Mode)
       ├─ Container::make()
       ├─ ServiceLoader (System\ServiceLoader\MainLoader)
       │    └─ registers all bindings / singletons
       ├─ ErrorHandler + ExceptionHandler installed
       └─ Mode dispatch → Console: run Application with commands
                        → HTTP: handle request
```

### Coding Conventions

- **Tabs** for indentation (enforced by PHP CS Fixer — Symfony base + custom rules)
- **PHP 8.3+** syntax; strict types everywhere
- PHPStan runs at **level max** — all code must pass with no errors
- Commit format: `feat(#ISSUE): message` / `fix(#ISSUE): message`
- No third-party runtime dependencies in `core/`; keep that boundary intact