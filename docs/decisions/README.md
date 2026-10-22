---
kind: guide
---

# Decisions — architecture decision records

The record of **irreversible choices** for the openEHR Assistant MCP Server: one decision per `ADR-NNNN`,
numbered sequentially and never reused, in a lightweight [MADR](https://adr.github.io/madr/) style. An ADR
states the fork, the choice, and its consequences; its Traceability list names the
[capabilities](../requirements.md) it shapes. A decision that every change must respect is also stated as a
MUST sentence in the [constitution](../architecture.md), which is what binds.

Record decisions with `/sdd-specify`, starting from [_template.md](_template.md). An ADR is `accepted` before
code depends on it; to change a decision, add a new ADR that supersedes it rather than editing history.

> **Note on the codebase-memory ADR.** A condensed architecture summary also lives in the `codebase-memory`
> knowledge graph (`manage_adr`) as AI-grounding context. These committed records are the human-reviewable
> source of truth; the in-graph copy is a convenience mirror.

## Status

`proposed` → `accepted` → `superseded by ADR-NNNN` | `deprecated`.

## Index

The table below is generated from the decision records by `sdd-check generate`. Never hand-edit it.

<!-- sdd:generated adr-index -->

| ID | Title | Status | Date | Resolves / amends |
|---|---|---|---|---|
| ADR-0001 | Attribute-driven capability discovery with startup cache | Accepted | 2026-06-08 | Amends: REQ-N4 (fast startup), supports REQ-F1–F8 |
| ADR-0002 | Single CkmClient external HTTP boundary, mocked in tests | Accepted | 2026-06-08 | Amends: REQ-F1 (CKM access), REQ-N2 (deterministic, offline tests) |
| ADR-0003 | Split global policy from task-specific prompt content | Accepted | 2026-06-08 | Amends: REQ-F6 (prompts), REQ-F10 (server instructions), REQ-N7 (context economy) |
| ADR-0004 | Docker-only runtime; no host PHP/Composer | Accepted | 2026-06-08 | Amends: REQ-N5 (reproducible runtime) |
| ADR-0005 | Authoritative, cheapest-first specification retrieval | Accepted | 2026-06-08 | Amends: REQ-N1 (spec alignment, no guessing) |
| ADR-0006 | Machine-checked traceability with a spec-check drift gate | Deprecated | 2026-07-19 | Amends: REQ-N8 (machine-checked traceability); governs the whole `REQ-*` spec set |
| ADR-0007 | The public website lives in its own repository | Accepted | 2026-08-07 | Amends: REQ-N10 (install documentation is consumable without duplication) |

<!-- /sdd:generated -->
