# ADR-0001 — Attribute-driven capability discovery with startup cache

- **Status:** Accepted
- **Requirements:** REQ-N4 (fast startup), supports REQ-F1–F8
- **Related:** [architecture.md](../architecture.md)

## Context

The server exposes many MCP capabilities (tools, prompts, resources, completion
providers) and that set grows over time. Capabilities could be wired up with an
explicit central registry, or discovered from the code itself. A central
registry is one more place to keep in sync with every new class; missing an
entry is a silent failure. Re-scanning all classes on every process start,
however, adds latency for a server that clients connect to repeatedly.

## Decision

Declare every capability with the SDK's PHP attributes — `#[McpTool]`,
`#[McpPrompt]`, `#[McpResource]`/`#[McpResourceTemplate]`,
`#[CompletionProvider]` — and let the SDK discover them by scanning `src/`.
Cache the discovery result with a file-based cache (Symfony Cache, under
`XDG_DATA_HOME`, default `/tmp`) so the scan runs once and subsequent boots read
the cache.

## Consequences

- **Positive:** adding a capability is a single, local change (annotate a class);
  no registry to update, so the "forgot to register" failure mode disappears.
- **Positive:** startup stays fast after the first scan.
- **Negative / gotcha:** the discovery cache must be cleared after adding or
  renaming a tool/resource/prompt class, or the new capability will not appear.
  Clear the cache directory under `XDG_DATA_HOME` (see [development.md](../development.md)).
- Attribute names (`name:` arguments) become part of the public MCP contract —
  renaming one is a breaking change for clients.
