# Token & Context Optimization

## Task
Optimize MCP Elements and AI Agents in `/var/www/html/app/Assist/AI` to minimize context/token usage without reducing result quality.

Read docs first: [PHP MCP Server](https://github.com/php-mcp/server) · [Neuron AI Agents](https://docs.neuron-ai.dev)

## Goals
1. Reduce token usage per interaction.
2. Maximize context relevance — no redundant data in prompts.
3. Deterministic, structured responses (short JSON keys, no prose in tool returns).
4. Cache stable data via MCP Resources instead of re-reading via Tools.
5. Add/modify MCP Elements where it reduces round-trips.

## Constraints
- AI elements only in `/var/www/html/app/Assist/AI`.
- Output must be concise. Avoid explanations unless requested.
- Prefer structured output over natural language.
- No redundancy. Don't repeat user input.
