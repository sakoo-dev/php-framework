# File Handling & Processing Reference

## Key Principles
- **Stat before read**: Check file size before loading. Large files need sampling.
- **Read just enough**: Match reading depth to the question. `wc -l` for row count, `head` for preview.

## Reading by File Size

| Size | Strategy |
|------|----------|
| < 20KB | Full read is fine |
| 20KB–100KB | Read relevant sections only |
| > 100KB | Sample: head + tail, or grep for specifics |

## Batch & Navigation (MCP)
- Use `read_files` tool to read multiple files in one call — eliminates N sequential round-trips.
- Use `project_structure` tool before `read_file` to locate files without loading all paths.
- Prefer `project://structure` resource (cached per session) over the tool (dynamic).
- Navigate: structure → target file → read → act.

## Token Cost Awareness
- Average PHP source file: ~300–800 tokens.
- Large class: ~1,000–2,000 tokens.
- Full project structure listing: ~500–1,500 tokens.
- Tool-call overhead: ~50 tokens per call. Batch to save.

## Safety Rules
- Never write to vendor/, .git/, or node_modules/.
- Never log sensitive data (passwords, tokens, API keys).
- Validate paths before write — reject path traversal.
- Use `Assert::file()` and `Assert::notDir()` before reading.
