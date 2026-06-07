# Architecture — openEHR Assistant MCP Server

> Part of the [Specification-Driven Development docs](README.md). This describes
> *how* the system satisfies [requirements.md](requirements.md). Each component
> notes the `REQ-#` it serves; the full mapping is in
> [traceability.md](traceability.md). Design choices are recorded as
> [decision records](decisions/).

## Overview

A PHP 8.4 server built on [`modelcontextprotocol/php-sdk`](https://github.com/modelcontextprotocol/php-sdk).
Capabilities are declared with PHP attributes and discovered automatically at
startup; discovery results are cached. A single outbound HTTP client reaches the
CKM REST API. Domain content (guides, examples, terminology, BMM type specs)
ships as static resources under `resources/`.

```
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

### Entry point — `public/index.php` · REQ-F9, REQ-N4
Registers tools, prompts, resources, and completion providers; selects the
transport (`--transport=stdio`, else `streamable-http`); starts the server. Uses
a file-based discovery cache (Symfony Cache, under `XDG_DATA_HOME`, default
`/tmp`) so attribute scanning is not repeated on every boot.
→ [ADR-0001](decisions/0001-attribute-driven-discovery.md)

### Tools — `src/Tools/` · REQ-F1–F5
Service classes whose public methods are annotated `#[McpTool(name: '…')]`. Each
is the MCP-facing surface for one knowledge domain:

| Class | Tools | REQ |
|-------|-------|-----|
| `CkmService` | `ckm_archetype_search/get`, `ckm_template_search/get` | REQ-F1 |
| `GuideService` | `guide_search`, `guide_get`, `guide_adl_idiom_lookup` | REQ-F2 |
| `ExamplesService` | `examples_search`, `examples_get` | REQ-F3 |
| `TerminologyService` | `terminology_resolve` | REQ-F4 |
| `TypeSpecificationService` | `type_specification_search`, `type_specification_get` | REQ-F5 |

### Prompts — `src/Prompts/` · REQ-F6
All extend `AbstractPrompt`, which loads message bodies from markdown under
`resources/prompts/` via `loadPromptMessages()`. Each prompt class exposes a
single `__invoke`. Two families: **explorers** (`*_explorer`) that orchestrate
discovery/retrieval, and **explain / design_or_review** prompts per artefact
kind, plus `fix_adl_syntax` and `translate_archetype_language`.
→ [ADR-0003](decisions/0003-prompt-policy-split.md)

### Resources — `src/Resources/` · REQ-F7
Expose retrievable content under stable URIs:

| Class | URI | REQ |
|-------|-----|-----|
| `Guides` | `openehr://guides/{category}/{name}` | REQ-F7 / REQ-F2 |
| `Examples` | `openehr://examples/{kind}/{name}` | REQ-F7 / REQ-F3 |
| `TypeSpecifications` | `openehr://spec/type/{component}/{name}` | REQ-F7 / REQ-F5 |
| `Terminologies` | `openehr://terminology` | REQ-F7 / REQ-F4 |

Guide categories: `archetypes`, `templates`, `aql`, `simplified_formats`,
`specs` (per-document spec digests), `howto` (toolchain how-tos). Example kinds:
`aql`, `flat`, `structured`, `archetypes`.

### Completion providers — `src/CompletionProviders/` · REQ-F8
`Examples`, `Guides`, `SpecificationComponents` — implement
`Mcp\Capability\Completion\ProviderInterface`, annotated `#[CompletionProvider]`,
to suggest argument values for prompts/resource templates.

### External boundary — `src/Apis/CkmClient` · REQ-F1, REQ-N2
The **only** outbound HTTP client (Guzzle). `request` / `requestAsync` wrap the
CKM REST API. Every CKM-touching test mocks this client; live CKM is never hit
in tests. → [ADR-0002](decisions/0002-single-ckmclient-http-boundary.md)

### Helpers — `src/Helpers/`
`CliOptions` (transport option parsing), `Map` (data shaping),
`TerminologyXmlLoader` (loads the bundled openEHR terminology XML).

### Static content — `resources/`
`guides/`, `examples/`, `bmm/` (BMM JSON for type specs), `terminology/`,
`prompts/` (prompt message bodies), `server-instructions.md` (REQ-F10).

## Cross-cutting design rules

- **Global policy vs task policy (REQ-N7).** Always-applicable policy (tool
  discipline, no guessing, Guide/Spec/Digest/Examples-First) lives in
  `resources/server-instructions.md`; `resources/prompts/*.md` carry only
  task-specific constraints. Enforced by `PromptPolicySeparationTest`.
  → [ADR-0003](decisions/0003-prompt-policy-split.md)
- **Spec alignment (REQ-N1).** Standards content is retrieved from authoritative
  sources, cheapest representation first.
  → [ADR-0005](decisions/0005-spec-aligned-content-retrieval.md)
- **Test mirror (REQ-N2).** Each `src/` class has a parallel `tests/…/*Test`.
  Two extra guard tests cross-check invariants: `PromptCompositionTest` (prompt
  size baselines in `tests/fixtures/prompt_lengths_before_shared.json`) and
  `PromptPolicySeparationTest`.
- **Docker-only runtime (REQ-N5).** See [development.md](development.md).
  → [ADR-0004](decisions/0004-docker-only-runtime.md)

## Versioning

Application version is defined in `src/constants.php` (`APP_VERSION`, currently
`0.16.0`). Release process and CHANGELOG conventions live in
[AGENTS.md](../AGENTS.md) (Coding style → CHANGELOG entries) and `CHANGELOG.md`.
