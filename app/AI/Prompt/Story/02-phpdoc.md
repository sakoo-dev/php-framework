# PHPDoc Generation for Core

## Task
Generate PHPDocs (documentation only, not code) for core components at `/var/www/html/core/src`.

## Process
For each file, read it then write documentation immediately.

## Rules
- Document class purpose and public method behavior — no `@return` or `@param` annotations.
- Only `@throws` is allowed from the annotation set.
- Use `[at-sign]` instead of `@` when describing annotations in prose (prevents parser breakage).
- Preserve existing PHPDocs, especially PHPStan annotations.
- Skip methods that already have documentation.
