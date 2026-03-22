# HTTP Foundation Core for Sakoo Framework

## Context

You are working inside the **Sakoo PHP Framework** codebase at `/var/www/html/`.
Do **not** assume anything. Read every file above before proceeding.

## Task

Create the complete **HTTP Foundation Core** module for the Sakoo framework under `core/src/Http/`. This module is the layer between the Swoole HTTP server and the Sakoo Kernel. It must handle concurrent requests using Swoole coroutines, translate Swoole's raw server objects into PSR-7-compatible value objects, dispatch them through a PSR-15 middleware pipeline, match them against a typed route registry, and emit the PSR-7 response back to Swoole.

Every file you create must be written to the filesystem using MCP Tools. Do not display code blocks and ask for confirmation — write every file directly. After all files are written, print a final summary table of every path created.

### Namespace & File Rules

- Root namespace for this module: `Sakoo\Framework\Core\Http`
- Every file begins with `<?php` + blank line + `declare(strict_types=1);`
- Every `use` block is sorted: PHP stdlib → PSR interfaces → Sakoo Core → Sakoo Http (current module)
- Every method has a declared return type — including `void`, `never`, `static`, `self`
- Constructor property promotion for all injected dependencies
- `throwIf()` / `throwUnless()` instead of bare `if (...) throw`
- No `new \DateTime()` — inject `Psr\Clock\ClockInterface` where time is needed
- No hardcoded paths — use `Path::` static helpers
- `set([])` for all homogeneous typed collections, typed as `Set<T>` in PHPDoc
- HTTP Entry points are `public/index.php` and `public/server.php`
