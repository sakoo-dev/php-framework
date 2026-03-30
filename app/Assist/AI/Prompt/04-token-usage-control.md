## TASK:
Your task is to design a highly efficient pipeline for MCP Elements and AI Agents according to below Docs:
- MCP Elements (https://github.com/php-mcp/server)
- Neuron AI Agents (https://docs.neuron-ai.dev/)

to minimize Context and Token Usage of Cluade/OpenAI LLMs.

## GOALS:
1. Minimize token usage
2. Maximize context relevance
3. Avoid redundant or verbose outputs
4. Ensure deterministic, structured responses
5. Be production-ready (stateless, composable, debuggable)
6. Using cache if possible instead of reading MCP Tools or Resources every time

---

## REQUIREMENTS:

Design a pipeline with the following stages:

1. INPUT NORMALIZATION
    - Clean user input
    - Remove noise, duplicates, unnecessary text
    - Extract intent and key entities

2. CONTEXT SELECTION
    - Select only relevant context
    - Apply truncation, ranking, or embedding-based filtering
    - Limit context to essential data only

3. PROMPT CONSTRUCTION
    - Use minimal tokens
    - Avoid repetition
    - Use structured format (JSON or bullet logic)
    - Include only necessary instructions

4. MODEL EXECUTION STRATEGY
    - Choose between:
        - direct answer
        - tool usage
        - multi-step reasoning
    - Avoid chain-of-thought unless required

5. OUTPUT POST-PROCESSING
    - Normalize response
    - Remove unnecessary verbosity
    - Convert to structured format if needed
    - Enforce schema compliance

6. MEMORY / STATE (optional)
    - Store only critical data
    - Avoid long-term noisy memory

---

## CONSTRAINTS:

- Output MUST be concise
- Avoid explanations unless explicitly requested
- Prefer structured outputs over natural language
- Use token-efficient patterns (short keys, no redundancy)
- Avoid repeating user input
- Avoid unnecessary system prompts

---

## EXTRA OPTIMIZATION RULES:

- Replace long keys with short aliases where possible
- Use enums instead of long strings
- Prefer arrays over verbose objects when safe
- Compress repeated patterns
- Avoid natural language where structured data works
