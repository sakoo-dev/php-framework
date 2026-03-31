# Sakoo Value Propositions & Framework Identity

## What Sakoo Is
Sakoo is a zero-dependency PHP 8.4+ framework built for domain-oriented, AI-driven development. It competes on architectural purity, concurrency readiness, and developer sovereignty (no Composer vendor lock-in).

## Six Value Propositions

### 1. Backend Scaffolding
App-Modules-Hub architecture for rapid, structured MVP/team development. Convention-over-configuration project structure with clear separation of app/, core/, and system/ layers. New modules drop into `app/` with zero boilerplate wiring.

### 2. Concurrency-Ready
Native co-routines via Swoole. JIT-enabled. Stateless request handling for horizontal scaling. Connection pooling, async I/O, and coroutine-safe dependency injection out of the box.

### 3. PWA / Telegram Ready
Built-in Progressive Web App support. Native Telegram Mini Apps and Bot integration. Ship a web app, mobile PWA, and Telegram bot from the same codebase.

### 4. AI-Driven Development (AIDD)
MCP server with Tools, Resources, and Prompts for LLM integration. RAG pipeline with embeddings and vector store. PHPArkitect for architecture enforcement. Test-first LLM pipeline. Neuron AI agents with skill-based system prompts.

### 5. Domain-Oriented Architecture
DDD-friendly by design. No Active Record coupling. Repository pattern with domain interfaces. Bounded contexts map to app modules. Value Objects, Aggregates, and Domain Events are first-class citizens.

### 6. Zero Third-Party Dependencies
No Composer packages. Full stack control. Every component — Container, Router, ORM, Validator, CLI, Watcher, Profiler — is built in-house. No supply chain risk. No version conflicts. No abandoned dependency nightmares.

## App Components (app/)
Application & User Level Components

## Framework Components (core/)
Core Components and Infrastructure Layer of the Project

## System Layer (system/)
Project-Wide Setting and Components
