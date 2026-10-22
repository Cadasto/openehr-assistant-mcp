# openEHR Assistant MCP Server architecture

This page describes *how* the server satisfies [requirements.md](requirements.md),
for contributors who need to know where a capability lives before changing it. It
is part of the [Specification-Driven Development docs](README.md): each component
notes the `REQ-#` it serves, the full mapping is in [traceability.md](traceability.md),
and design choices are recorded as [decision records](decisions/).

## Overview

A PHP 8.4 server built on [`modelcontextprotocol/php-sdk`](https://github.com/modelcontextprotocol/php-sdk).
Capabilities are declared with PHP attributes and discovered automatically at
startup; discovery results are cached. A single outbound HTTP client reaches the
CKM REST API. Domain content (guides, examples, terminology, BMM type specs)
ships as static resources under `resources/`.

```text
MCP client ──(streamable-http :8343 | stdio)──▶ public/index.php
                                                  │  attribute discovery (cached)
                    ┌─────────────────────────────┼─────────────────────────────┐
                    ▼                ▼             ▼              ▼               ▼
                 Tools           Prompts       Resources   CompletionProviders  server-instructions.md
              (src/Tools)     (src/Prompts)  (src/Resources)  (src/Completion…)   (resources/)
                    │
                    ▼
              Apis/CkmClient ──HTTP──▶ CKM REST API   (the only external boundary)
                    │
              resources/  (guides · examples · bmm · terminology)
```

## Layers

### Entry point: `public/index.php` · REQ-F-009, REQ-N-004
Registers tools, prompts, resources, and completion providers; selects the
transport (`--transport=stdio`, else `streamable-http`); starts the server. Uses
a file-based discovery cache (Symfony Cache, under `XDG_DATA_HOME`, default
`/tmp`) so attribute scanning is not repeated on every boot.
→ [ADR-0001](decisions/0001-attribute-driven-discovery.md)

### Tools: `src/Tools/` · REQ-F-001 to REQ-F-005, REQ-N-009
Service classes whose public methods are annotated `#[McpTool(name: '…')]`. Each
is the MCP-facing surface for one knowledge domain:

| Class | Tools | REQ |
|-------|-------|-----|
| `CkmService` | `ckm_archetype_search/get`, `ckm_template_search/get` | REQ-F-001 |
| `GuideService` | `guide_search`, `guide_get`, `guide_adl_idiom_lookup` | REQ-F-002 |
| `ExamplesService` | `examples_search`, `examples_get` | REQ-F-003 |
| `TerminologyService` | `terminology_resolve` | REQ-F-004 |
| `TypeSpecificationService` | `type_specification_search`, `type_specification_get` | REQ-F-005 |

### Prompts: `src/Prompts/` · REQ-F-006
All extend `AbstractPrompt`, which loads message bodies from markdown under
`resources/prompts/` via `loadPromptMessages()`. Each prompt class exposes a
single `__invoke`. Two families: **explorers** (`*_explorer`) that orchestrate
discovery/retrieval, and **explain / design_or_review** prompts per artefact
kind, plus `fix_adl_syntax` and `translate_archetype_language`.
→ [ADR-0003](decisions/0003-prompt-policy-split.md)

### Resources: `src/Resources/` · REQ-F-007
Expose retrievable content under stable URIs:

| Class | URI | REQ |
|-------|-----|-----|
| `Guides` | `openehr://guides/{category}/{name}` | REQ-F-007 / REQ-F-002 |
| `Examples` | `openehr://examples/{kind}/{name}` | REQ-F-007 / REQ-F-003 |
| `TypeSpecifications` | `openehr://spec/type/{component}/{name}` | REQ-F-007 / REQ-F-005 |
| `Terminologies` | `openehr://terminology` | REQ-F-007 / REQ-F-004 |

Guide categories: `archetypes`, `templates`, `aql`, `simplified_formats`,
`specs` (per-document spec digests), `howto` (toolchain how-tos). Example kinds:
`aql`, `flat`, `structured`, `archetypes`.

### Completion providers: `src/CompletionProviders/` · REQ-F-008
`Examples`, `Guides`, and `SpecificationComponents` implement
`Mcp\Capability\Completion\ProviderInterface`, annotated `#[CompletionProvider]`,
to suggest argument values for prompts/resource templates.

### External boundary: `src/Apis/CkmClient` · REQ-F-001, REQ-N-002
The **only** outbound HTTP client (Guzzle). `request` / `requestAsync` wrap the
CKM REST API. Every CKM-touching test mocks this client; live CKM is never hit
in tests. → [ADR-0002](decisions/0002-single-ckmclient-http-boundary.md)

### Helpers: `src/Helpers/`
`CliOptions` (transport option parsing), `Map` (data shaping),
`TerminologyXmlLoader` (loads the bundled openEHR terminology XML),
`SearchTokenizer` (the one query tokenizer shared by `guide_search` and
`examples_search`, so the two cannot rank the same query differently).

### Static content: `resources/`
`guides/`, `examples/`, `bmm/` (BMM JSON for type specs), `terminology/`,
`prompts/` (prompt message bodies), `server-instructions.md` (REQ-F-010).

## Cross-cutting design rules

- **Global policy vs task policy (REQ-N-007).** Always-applicable policy (tool
  discipline, no guessing, Guide/Spec/Digest/Examples-First) lives in
  `resources/server-instructions.md`; `resources/prompts/*.md` carry only
  task-specific constraints. Enforced by `PromptPolicySeparationTest`.
  **Resilience exception:** `resources/prompts/shared/policy.md` is a thin
  user-role block prepended to every prompt that restates a subset of
  Guide-First / output-contract globals, so clients that under-inject the MCP
  `instructions` field still receive core policy; `server-instructions.md`
  remains the sole canonical source for full global policy.
  → [ADR-0003](decisions/0003-prompt-policy-split.md)
- **Schemas are the wire contract (REQ-N-009).** Tool schemas are published to
  clients, and the SDK checks both halves at runtime, but differently:
  `CallToolHandler` validates arguments against the generated `inputSchema`
  before a method runs and refuses a bad call, while since `mcp/sdk` 0.9 a
  return value that fails its `outputSchema` is replaced by a failed
  (`isError`) tool result, so output drift that ships breaks the tool for
  every caller. The contract is therefore pinned in CI from both sides:
  `InputSchemaGuardTest` reflects every `#[McpTool]` method for closed,
  self-consistent inputs, and `OutputSchemaConformanceTest` checks each tool's
  output, also through the SDK's own validator on the value as sent, before it
  can reach a client. The authoring rules that satisfy this live in
  [conventions.md](conventions.md#mcp-capabilities-authoring).
- **Spec alignment (REQ-N-001).** Standards content is retrieved from authoritative
  sources, cheapest representation first.
  → [ADR-0005](decisions/0005-spec-aligned-content-retrieval.md)
- **Test mirror (REQ-N-002).** Each `src/` class has a parallel `tests/…/*Test`.
  Extra guard tests cross-check invariants, among them `PromptCompositionTest`
  (prompt size baselines in `tests/fixtures/prompt_lengths_before_shared.json`) and
  `PromptPolicySeparationTest`; [testing.md](testing.md#guard-tests) lists them.
- **Docker-only runtime (REQ-N-005).** See [development.md](development.md).
  → [ADR-0004](decisions/0004-docker-only-runtime.md)

## Documentation boundary · REQ-N-010

Contributor and user documentation stays in this repository; the public product
website is a separate repository, [cadasto/openehr-assistant](https://github.com/cadasto/openehr-assistant),
which documents this server *and* the plugin. It holds no copy of the install
instructions; it fetches [install.md](install.md) at a released tag, which makes
that file's path and shape an external contract, guarded by
`tests/Content/InstallDocContractTest.php`.
→ [ADR-0007](decisions/0007-website-in-separate-repository.md)

## Versioning

Application version is defined in `src/constants.php` (`APP_VERSION`), the single
source, deliberately not restated here. It also namespaces the discovery cache, so
a release bump invalidates stale capability entries
([ADR-0001](decisions/0001-attribute-driven-discovery.md)).
Release process and CHANGELOG conventions live in
[AGENTS.md](../AGENTS.md) (Conventions) and `CHANGELOG.md`.
