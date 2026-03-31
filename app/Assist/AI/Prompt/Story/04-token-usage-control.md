## TASK
Your task is to make highly efficient MCP Elements and AI Agents according to below Docs:
- PHP MCP Server (https://github.com/php-mcp/server)
- Neuron AI Agents (https://docs.neuron-ai.dev)

to minimize Context and Token Usage of Cluade/OpenAI LLMs **without reduce Quality of Results**.

> I use **Claude Desktop** with it's MCP elements. focus on the elements of using this Approach instead of Agents Only.

## GOALS
1. Reduce token usage
2. Maximize context relevance
3. Avoid redundant or verbose outputs
4. Ensure deterministic, structured responses
5. Be production-ready (stateless, composable, debuggable)
6. Using cache if possible instead of reading MCP Tools or Resources every time (if it has any impact)
7. Adding new MCP Elements (if it has any impact)

## CONSTRAINTS
- AI elements in this Project are placed in `/var/www/html/app/Assist/AI`. Don't Lookfor them any other Place.
- Read `PHP MCP Server` and `Neuron AI Agents` Documentation to make changes according to their **Best Practices**
- Output MUST be concise
- Avoid explanations unless explicitly requested
- Prefer structured outputs over natural language
- Use token-efficient patterns (short keys, no redundancy)
- Avoid repeating user input
- Avoid unnecessary system prompts
