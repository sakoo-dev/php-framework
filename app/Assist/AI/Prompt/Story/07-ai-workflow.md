# AI Workflow Engine

## Introduction
This project uses [NeuronAI](https://packagist.org/packages/neuron-core/neuron-ai) library to add ability of creating **AI Agents** to Sakoo PHP Framework.
Documentation & Reference of this Library is [here](https://docs.neuron-ai.dev/).

Also this Project uses [PHP Official MCP SDK](https://packagist.org/packages/mcp/sdk) to add **Model Context Protocol** support.
Documentation & Reference of this Library is [here](https://php.sdk.modelcontextprotocol.io/docs/index.html).

Our Purposes in this AI Ecosystem summerized in two main category:
- **Creating Development Tools**
- **Creating Product Based Applications**

So to fulfill these Goals, we need to use Full Abilities of these libraries and your suggested libraries.

## Workflow
NeuronAI created at the top of a [Workflow Engine](https://docs.neuron-ai.dev/workflow/getting-started) that works with Nodes, Events, Middlewares and etc.
This Workflow structure is suitable for **Model and maintain complex scenario**, **Human in the Loop**, Streaming, Debugging and ...

Workflows can design a complete Process using [Loops & Branches](https://docs.neuron-ai.dev/workflow/loops-and-branches) architecture.
`WorkflowState` is the shared mutable bag passed through every node.
You can Learn How to work Professional with NeuronAI [here](https://docs.neuron-ai.dev/workflow/examples).
Evaluations & Assertions can be written based on [this doc](https://docs.neuron-ai.dev/agent/evaluation).
Also Automated Tests can be written based on [this doc](https://docs.neuron-ai.dev/agent/testing).

## Constraints
- [ ] Readability is most important thing in our code base
- [ ] Use native NeuronAI components: `Node`, `WorkflowState`, `WorkflowMiddleware`, `Workflow`, events, etc.
- [ ] SOLID: one responsibility per class; depend on interfaces not concretions.
- [ ] No `static` methods in domain — statics hide dependencies, break testability, and prevent DI.
- [ ] Exceptions must be named and typed (no bare `Sakoo\Exception`).
- [ ] Each component ships with: its own Event class(es), DTO(s), VO(s), typed Exception(s), and Interface(s).
- [ ] General framework core components Directory:`core/src/`
- [ ] General AI components Directory: `app/Assist/AI/`
- [ ] NeuronAI ecosystem components Directory: `app/Assist/AI/Neuron/`

## Vision: Sustainable AI Engine
> We want to create and tune AI Agents in 2 Layers: Code & UI
> 
> In code, it's an obvious topic and we already created it. but in UI We needs an Interface to create Agents and Configure Provider, Model, Temprature, Max Tokens, TopP, Cost Limit, MCP Connections (1st Party and 3rd Party), Skills, RAG and Embedding Engine, Message History, REST Endpoints and API Keys and Webhooks, Token Dashboard and etc.
- Availability
- Consistency
- Security
- Observability
- Accuracy
- Scalability
- Integrity

## Nice to have
Ability to enable/disable or Customize Direction of Flow using Feature Flag or Any simple method.

## Tasks
- First of all, read my Prompt and Stop Process and Suggest me to use `Opus` or `Sonnet` Model if current model is different.
- Do Tasks One by One, After Code Checking and Quality Assurance and my Approval, Start Next Task.
- Feel free to Ask me anything, but update this file according to my responses.
- Don't be Verbose, Only Say Essential things.

## Task 0: Dynamic Chat History
Checks `storage/ai/chat-history/*` for an existing session.
If file exists and `$userInput->get('resumeSession') === true`: load history into `BaseAgent`.
If not: start fresh session, write new file.

## Task 1: Audit & Observability
> Full observability and audit at every step including tokens and USD cost.

Generalize existing AI audit and observers (McpTokenCalculator, McpTokenObserver, `logger.ai`) with a Single Responsible and Complete one to use everywhere of this AI ecosystem.

According to Docs, [Agent Middlewares](https://docs.neuron-ai.dev/agent/middleware) and [Workflow Middleware](https://docs.neuron-ai.dev/workflow/middleware) and [Persistance Capability](https://docs.neuron-ai.dev/workflow/persistence) in NeuronAI are good choice to Control & Monitor (Track agent behavior with logging, analytics, and debugging)

We want all data that can be used in Our Metrics and Dashboards.

- Store data into `storage/ai/metrics` directory
- Create file + parent dirs if missing — never throw on missing file.
- Daily file: `storage/ai/metrics/{YYYY-MM-DD}.jsonl` (one JSON object per line).
- Make an Adapter to easily change from `jsonl` to any Database or format Store.

> Some of Components like ChatHistory should be Encrypted to fulfill users Privacy

### What Matters?
```
- sessionId // to create relation between ChatHistory, Logs, ...
- timestamp // ISO 8601
- agent
- model
- provider
- source // enum: Live | Cache
- tokensIn
- tokensOut
- priceUsd
- priceIrt // I will fill it later
- latencyMs // Per Step
- qualityScore // 0–1 from QualityEvaluatorInterface; NullQualityEvaluator returns null
- feedback // user-provided signal
```
It's not final Data Structure. you can make it complete or better based on your desired Architecture.

Tip: You can use NeuronAI's built-in middleware hooks (`WorkflowMiddleware::before/after`) for cross-cutting logging.

## Task 2: Availability
There are some Strategies in Computer Science to make Distributed System High Available.
I've listed some of them below. but I don't know which of them are Best Practice to implementation in an AI Agent Engine system.
According to our effort to prevent system complexity, choose some of them to make this system High Available and keep Simple and Readable. 

### Circuit Breaker
Wraps the outbound HTTP call to the LLM provider using `HttpClient`.
States: `Closed | Open | HalfOpen` (enum `CircuitState`).
When `Open`: throw `CircuitOpenException` immediately — do not call provider.
Why: prevents cascade failures; stops hammering a degraded provider.

### Throttle
Rate-limit LLM requests per `agentName` + `userId` composite key.
Storage backend: `ThrottleStorageInterface` (file, Redis, DB — implement separately).
When limit exceeded: throw `ThrottleLimitExceededException` with `retryAfterSeconds`.

### Retry & Backoff
Retries the LLM call when the response has Error.
After exhausting attempts: throw `MaxRetriesExceededException`.
Why: transient LLM failures should not surface as hard errors to the caller.

### Fallback Chain
Ordered list of `AIProviderInterface` instances injected at construction.
Tries each provider in sequence if current returns invalid response or throws.
Why: maintains quality SLA when primary model degrades.

### Queue & Load Balancer
Enqueues `AgentRequestDTO` when concurrency ceiling reached.
Why: sustains throughput under burst load without dropping requests.

### Cache
Caches LLM responses keyed on deterministic hash of `(model, systemPrompt, userMessage)`.
On hit: set `MetricSource::Cache` in `WorkflowState` before continuing pipeline.
On miss: set `MetricSource::Live`, run pipeline, store result.

## Task 3: Planning
- **Planner / Dry Run:**
  explicit planning enables cost forecasting, dry-run gating, and risk-aware execution.
  Show to user all your steps if they neeeds.
  Dry run Reads `Plan` from `WorkflowState`.
  Simulates execution: resolves tool calls and sub-agent tasks without hitting real providers.
  Returns `DryRunReport` DTO: `estimatedTokens`, `estimatedCostUsd`, `wouldTriggerThrottle`, `risks[]`, ...
  Throws `DryRunException` if simulation detects a blocking risk.
  Gate: if `WorkflowState::get('dryRun') === true`, stop here with `StopEvent` — never execute live.

## Task 4: Privacy & Security
Content Regulator Applies to inbound `RequestEvent` and outbound `ResponseEvent`.
  Writes audit line to `storage/ai/audit/{YYYY-MM-DD}.jsonl` using our audit/observability service in #Task1.
  Classifies content as one of: `Restricted | Confidential | Internal | Public` (enum `ContentClassification`).
  Privacy of Users is important to us. suggest any strategy to make it happen.
  Also any illegal, unethical, abuse, self harm and ... prompt or responses should be filtered.
  It can fulfill using Separate Planner & Executer Model, Code Analyses using Regex and Patterns, etc.

### Detection strategies
each implements `DetectionStrategyInterface->detect(string $text): DetectionResult`:
- `UnethicalContentDetector` — patterns: self-harm, manipulation, deception, harassment.
- `IllegalContentDetector` — patterns: CSAM, illegal weapons/drugs, IP piracy, regulatory violations.
- `PromptInjectionDetector` — detects attempts to change agent purpose, tone, process, or privacy contract.
- `PiiMaskingDetector` — masks email, phone, CC (Luhn-validated), SSN/Tax ID via regex; returns masked text.

Dont limit yourself to above List. Complete them if it's possible.

## Task 5: Scalability
** Parallel & Isolated Sub Agents (Using NeuronAI Workflow Branching)**

Parallel Sub-Agents
Runs tasks concurrently via `Swoole` (inject `ConcurrencyDriverInterface` — no hardcoded driver).
Collects `SubAgentResult[]` into `WorkflowState` keyed by task name.
Isolates failures: one sub-agent failure must not abort others; collect into `SubAgentResult::$error`.

## Task 6: R&D
- Which PHP Libraries can Complete this Ecosystem? Deep Research on it.
- Which LLMs Support MCP?
