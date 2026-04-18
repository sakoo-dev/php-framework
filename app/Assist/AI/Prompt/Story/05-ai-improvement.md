# AI MCP Elements & Agents Improvement
We want to Optimize MCP Elements and AI Agents in `/var/www/html/app/Assist/AI` to optimize results & context/token usage with improvement result quality.

## Tasks
- ✅ Cluade Desktop has Adaptive thinking (think if task is complex, not any time) feature. add it to my agents if is possible.
- ✅ Optimize `/var/www/html/app/AI/Prompt/Reference/security-checklist.md` Security Checklist to use in mcp resources.
- ✅ Comment & PHPDocs -> Check and Write in all files to **dont write comments, just phpdocs description for methods and classes. not any @annotations allowed except of @throws and PHPStan's**
- ✅ AiLogger & Logger are not clean, we should have one Logger instance but with different path and contents.
- ✅ Change tools to generate and replace **AI Diff** instead of re-writing all file in LLM (Reducing Token Usage Up to 90%)

## References & Background
I use these packages on my project to implement AI Agents:
- [PHP MCP Server](https://github.com/php-mcp/server)
- [Neuron AI Agents](https://docs.neuron-ai.dev)

## Constraints
- AI elements only in `/var/www/html/app/Assist/AI`.
- Output must be concise. Avoid explanations unless requested.
- Prefer structured output over natural language.
- No redundancy. Don't repeat user input & verbosity

## Follow-ups

### Applied
- **Logger consolidation** — `FileLogger` now takes `$path` as a constructor argument (defaulting to empty → falls back to `Path::getLogsDir()` at write time). Mutable `setPath()` replaced by immutable `withPath()` that returns a clone. A new `App\Assist\AI\Logger\AiLogger` subclass pins the destination to `storage/ai/logs` and is registered as a singleton in `AIServiceLoader`. All three call sites (`McpServer`, `AgentCommand`, `McpServerCommand`) now resolve `AiLogger::class` directly — no per-site `setPath()` boilerplate.
- **Diff tool moved to adapter layer** — `DiffHunk` relocated from `core/src/FileSystem/` to `App\Assist\AI\Mcp\Diff\`, restoring the hexagonal boundary (core filesystem no longer knows about unified-diff semantics). `applyPatch()` removed from the `Storage` interface and `Local` implementation. A new `PatchApplier` service encapsulates hunk parsing and file splicing.
- **Malformed diffs throw** — `DiffHeader::parse()` now raises `MalformedDiffException` on unparseable `@@` headers instead of silently falling back to `startLine: 0` (which previously caused splice corruption at the end of the file).
- **Error distinction in `apply_diff` tool** — `applyDiffTool` catches `MalformedDiffException` and `PatchWriteException` separately, returning distinct `CallToolResult::error()` messages so the LLM can self-correct on diff-format issues vs. retry on transient write failures.
- **Data-loss fix** — `PatchApplier` reads the full file via `readLines()` instead of the capped `readChunkText()`. The previous implementation silently truncated any file larger than ~50KB on patch write.
- **Per-agent thinking flag** — `BaseAgent::supportsThinking(): bool` added, defaulting to `false`. `AIServiceLoader` now exposes four Claude bindings: `ai.provider.{sonnet,opus}` (plain) and `ai.provider.{sonnet,opus}.thinking` (with `thinking` parameter + `interleaved-thinking` beta header). `ArchitectAgent` overrides the flag to `true` and resolves the thinking binding; `WorkerAgent` stays on the plain sonnet binding. Future agents opt in by overriding the flag — the default cost profile is no-thinking.
- **`DiffHeader` extracted from `DiffHunk`** — the header-parsing regex, named pattern constant, `fromHeader()` factory, and `isHeader()` static have been extracted into a new `App\Assist\AI\Mcp\Diff\DiffHeader` value object. `DiffHunk` is now a pure VO carrying `startLine` / `linesToRemove` / `replacementLines` with its `with*()` mutators; `DiffHunk::fromHeader()` accepts a parsed `DiffHeader` rather than a raw string. `PatchApplier` uses `DiffHeader::isHeader()` + `DiffHeader::parse()` directly. SRP cleanup — no behaviour change.
