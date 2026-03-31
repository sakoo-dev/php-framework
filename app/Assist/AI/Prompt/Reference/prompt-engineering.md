# Prompt Engineering Reference

Rules, patterns, and anti-patterns for writing efficient AI prompts (system prompts, MCP elements, agent instructions). Distilled from internal engineering skill standards.

## 3-Tier Prompt Architecture

```
Tier 1: System Prompt (always loaded, every message)
  → Identity, core rules, framework conventions
  → Budget: 400–600 tokens max
  → Must be minimal — every token here is paid on every turn

Tier 2: Task Prompt (loaded once per task invocation)
  → Specific task instructions, constraints, output format
  → Budget: 200–400 tokens
  → Sent as user message or MCP Prompt

Tier 3: Reference Material (loaded on-demand, cached)
  → Architecture patterns, coding standards, detailed examples
  → Budget: unlimited (loaded only when needed)
  → Served via MCP Resources (Claude Desktop caches per session)
```

## Token Efficiency Rules

### DO
- Imperative voice: "Validate input at adapter boundary" not "You should validate input..."
- Short JSON keys in tool responses: `{'n': 5, 'kw': 'search'}` not `{'total_count': 5, 'keyword': 'search'}`
- One concept per bullet. No compound sentences.
- Structured output over natural language in tool returns.
- Cache stable data via MCP Resources instead of re-reading via Tools.
- Batch reads: `read_files([a,b,c])` instead of 3× `read_file()`.
- Progressive disclosure: load details only when needed.

### DON'T
- Decorative Markdown (`---` separators, double newlines between every line).
- Redundant identity phrasing: "You are an elite principal senior architect" — one sentence suffices.
- Sections that restate prior sections (e.g., "Engineering Mindset" that just lists words from other sections).
- Embedding stable reference material in system prompts — use MCP Resources.
- Verbose tool descriptions — keep under 120 chars.
- Repeating user input back in tool responses.
- Comments/explanations in tool output unless requested.

## Skill/Prompt Writing Patterns

### Structure
```markdown
# [Role Title]

[One-line identity + mission statement]

## [Section Name]
- [Imperative rule]
- [Imperative rule]
```

### Explain WHY, not just WHAT
Bad: "No `static` methods in domain."
Good: "No `static` in domain — statics hide dependencies, break testability, and prevent DI."

LLMs are smart. When they understand the reasoning, they generalize to novel cases. Rigid rules without rationale produce brittle compliance.

### Examples Pattern
```markdown
## Format
**Example:**
Input: Added user authentication with JWT tokens
Output: feat(auth): implement JWT-based authentication
```

### Output Format Definition
```markdown
## Response Structure
Use this template:
# [Title]
## Summary
## Findings
## Recommendations
```

## MCP Element Design Contract

### Tools (LLM selects and invokes)
- For dynamic/stateful data.
- Short, descriptive names: `read_file`, `project_structure`.
- Short JSON keys in responses.
- No prose in return values.
- Tool descriptions: what it does + when to use it, under 120 chars.

### Resources (User/client attaches explicitly)
- For static/reusable context that doesn't change per request.
- Claude Desktop caches resources per session — huge token savings vs re-reading.
- Use for: system prompts, coding standards, architecture docs, project structure.
- URI convention: `prompt://system`, `reference://architecture`, `project://structure`.

### Prompts (User runs as template shortcuts)
- Compose system + user messages from pre-authored Markdown files.
- Include token count estimation for awareness.
- Keep prompts composable — one prompt per task type.

## Anti-Patterns to Avoid

1. **Flat monolith prompt**: Everything in one system prompt → split into 3 tiers.
2. **Decorative formatting**: `---`, excessive `#####`, double newlines → LLMs don't need visual separators.
3. **Identity inflation**: "elite world-class principal architect" → "senior PHP engineer".
4. **Restating context**: Don't tell the model what it already knows from its training.
5. **Embedding Anthropic's soul doc as RAG seed**: The model already has these values — seed with domain-specific knowledge instead.
6. **Copy-paste prompt sections**: If two prompts share a section, extract to a shared Resource.
7. **Overconstrained output**: Too many MUSTs and NEVERs make prompts fragile. Explain the reasoning and let the model generalize.

## Quality Checklist Before Deploying a Prompt

- [ ] System prompt under 600 tokens?
- [ ] Every sentence earns its tokens? (Remove anything that doesn't change model behavior)
- [ ] No decorative formatting?
- [ ] Stable reference material in MCP Resources, not inline?
- [ ] Tool responses use short keys and no prose?
- [ ] WHY explained for non-obvious rules?
- [ ] Tested with actual model — outputs match intent?
