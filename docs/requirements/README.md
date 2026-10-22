---
kind: guide
---

# Requirements — capability index

The registry of **what** the openEHR Assistant MCP Server must deliver. One file per `REQ-*`: `REQ-F-NNN` for
functional and `REQ-N-NNN` for non-functional requirements. This index **links** to each requirement's
canonical specification section under [specifications/](../specifications/README.md); it never duplicates
normative prose. When behaviour changes, update the requirement and its specification section **first**, then
the code and tests that cite them.

Add requirements with `/sdd-specify`. A requirement carries a capability, acceptance criteria, and
out-of-scope, with **no file paths and no implementation detail.** Start from [_template.md](_template.md).

## Purpose

The server exposes openEHR domain knowledge (archetypes, templates, guides, examples, terminology, and
Reference/Archetype Model type specifications) to AI agents over the
[Model Context Protocol](https://modelcontextprotocol.io). It is a *knowledge and authoring-assistance* server:
it helps agents discover, explain, design, and review openEHR artefacts. It is **not** a clinical data
repository and stores no patient data.

## Status axes

- **Spec stability**: `Draft` (binding now; wording may still change) → `Stable` (frozen) → `Deprecated`.
- **Implementation**: `proposed` · `planned` · `in_progress` · `partial` · `landed` · `shipped` · `deferred` ·
  `retired`.

These are tracked **separately**: a `Draft` requirement is authoritative even while its code is `proposed`.

## Index

The table below is generated from the traceability map by `sdd-check generate`. Never hand-edit it.

<!-- sdd:generated requirements-index -->

| ID | Title | Spec | Stability | Implementation |
|---|---|---|---|---|
| [REQ-F-001](REQ-F-001-ckm-retrieval.md) | CKM archetype and template search and retrieval | [`SPEC-TOOLS §1`](../specifications/knowledge-tools.md#ckm-retrieval-req-f-001) | Draft | landed |
| [REQ-F-002](REQ-F-002-guides.md) | Guide discovery, retrieval and ADL idioms | [`SPEC-TOOLS §2`](../specifications/knowledge-tools.md#guides-req-f-002) | Draft | landed |
| [REQ-F-003](REQ-F-003-examples.md) | Curated examples search and retrieval | [`SPEC-TOOLS §3`](../specifications/knowledge-tools.md#examples-req-f-003) | Draft | landed |
| [REQ-F-004](REQ-F-004-terminology.md) | Terminology resolution | [`SPEC-TOOLS §4`](../specifications/knowledge-tools.md#terminology-req-f-004) | Draft | landed |
| [REQ-F-005](REQ-F-005-type-specifications.md) | Type specification lookup (BMM) | [`SPEC-TOOLS §5`](../specifications/knowledge-tools.md#type-specifications-req-f-005) | Draft | landed |
| [REQ-F-006](REQ-F-006-prompts.md) | Guided MCP prompts | [`SPEC-SURFACE §1`](../specifications/mcp-surface.md#prompts-req-f-006) | Draft | landed |
| [REQ-F-007](REQ-F-007-resources.md) | Resource exposure via openehr:// URIs | [`SPEC-SURFACE §2`](../specifications/mcp-surface.md#resources-req-f-007) | Draft | landed |
| [REQ-F-008](REQ-F-008-completion.md) | Argument auto-completion | [`SPEC-SURFACE §3`](../specifications/mcp-surface.md#completion-req-f-008) | Draft | landed |
| [REQ-F-009](REQ-F-009-transports.md) | Dual transport (streamable-http and stdio) | [`SPEC-SURFACE §4`](../specifications/mcp-surface.md#transports-req-f-009) | Draft | landed |
| [REQ-F-010](REQ-F-010-server-instructions.md) | Global server instructions | [`SPEC-SURFACE §5`](../specifications/mcp-surface.md#server-instructions-req-f-010) | Draft | landed |
| [REQ-N-001](REQ-N-001-spec-grounding.md) | Authoritative spec grounding | [`SPEC-CONTENT §1`](../specifications/content.md#grounding-req-n-001) | Draft | landed |
| [REQ-N-002](REQ-N-002-tests-and-mocked-http.md) | Mirrored tests and mocked external HTTP | [`SPEC-ENGINEERING §1`](../specifications/engineering.md#tests-and-http-boundary-req-n-002) | Draft | landed |
| [REQ-N-003](REQ-N-003-static-analysis.md) | Static analysis and code style | [`SPEC-ENGINEERING §2`](../specifications/engineering.md#static-analysis-req-n-003) | Draft | landed |
| [REQ-N-004](REQ-N-004-discovery-cache.md) | Cached discovery and fast startup | [`SPEC-SURFACE §6`](../specifications/mcp-surface.md#discovery-cache-req-n-004) | Draft | landed |
| [REQ-N-005](REQ-N-005-docker-only-runtime.md) | Docker-only, reproducible runtime | [`SPEC-ENGINEERING §3`](../specifications/engineering.md#docker-only-runtime-req-n-005) | Draft | landed |
| [REQ-N-006](REQ-N-006-conformance.md) | MCP conformance over HTTP | [`SPEC-SURFACE §7`](../specifications/mcp-surface.md#conformance-req-n-006) | Draft | landed |
| [REQ-N-007](REQ-N-007-content-economy.md) | Concise, AI-facing content | [`SPEC-CONTENT §2`](../specifications/content.md#content-economy-req-n-007) | Draft | landed |
| [REQ-N-008](REQ-N-008-drift-gate.md) | Machine-checked traceability drift gate | [`SPEC-ENGINEERING §4`](../specifications/engineering.md#drift-gate-req-n-008) | Draft | landed |
| [REQ-N-009](REQ-N-009-tool-schemas.md) | Published MCP tool schemas validated in CI | [`SPEC-TOOLS §6`](../specifications/knowledge-tools.md#tool-schemas-req-n-009) | Draft | landed |
| [REQ-N-010](REQ-N-010-install-doc.md) | Install docs canonical and externally consumable | [`SPEC-CONTENT §3`](../specifications/content.md#install-guide-req-n-010) | Draft | landed |

<!-- /sdd:generated -->

## Out of scope

- Storing or querying patient/EHR data (this is a knowledge server, not a CDR).
- Clinical end-user workflows (archetype authoring *by clinicians*, AQL writing *for a query*): those are the
  remit of the user-facing [openehr-assistant plugin](https://github.com/cadasto/openehr-assistant-plugin),
  which wraps this server.
- Bundling an MCP client or model.
