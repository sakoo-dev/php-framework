# HTTP Foundation Core

## Task
Create the complete HTTP Foundation module at `core/src/Http/`. This layer sits between Swoole HTTP server and the Sakoo Kernel: concurrent request handling via Swoole coroutines, PSR-7 value objects, PSR-15 middleware pipeline, typed route registry, and response emission back to Swoole.

Write every file to the filesystem via MCP Tools directly — no code blocks for confirmation. Print a summary table of all created paths when done.

## Rules
- Namespace root: `Sakoo\Framework\Core\Http`
- `<?php` + `declare(strict_types=1);` in every file.
- `use` sorted: PHP stdlib → PSR → Sakoo Core → current module.
- Every method has declared return type (including `void`, `never`, `static`, `self`).
- Constructor property promotion for all DI.
- No `new \DateTime` — inject `ClockInterface`. No hardcoded paths — use `Path::`.
- `set([])` for typed collections (`Set<T>` in PHPDoc).
- Entry points: `public/index.php` and `public/server.php`.
