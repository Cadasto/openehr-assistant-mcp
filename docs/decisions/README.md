# Architecture Decision Records

Architecturally significant decisions for the openEHR Assistant MCP Server,
recorded in a lightweight [MADR](https://adr.github.io/madr/) style. Each record
is immutable once `Accepted`; to change a decision, add a new ADR that
`Supersedes` it rather than editing history.

Part of the [Specification-Driven Development docs](../README.md). ADRs are
referenced from [requirements.md](../requirements.md),
[architecture.md](../architecture.md), and the
[traceability matrix](../traceability.md).

> **Note on the codebase-memory ADR.** A condensed architecture summary also
> lives in the `codebase-memory` knowledge graph (`manage_adr`) as AI-grounding
> context. These committed records are the human-reviewable source of truth; the
> in-graph copy is a convenience mirror.

| ADR | Title | Status | Requirements |
|-----|-------|--------|--------------|
| [0001](0001-attribute-driven-discovery.md) | Attribute-driven capability discovery with startup cache | Accepted | REQ-N4 |
| [0002](0002-single-ckmclient-http-boundary.md) | Single `CkmClient` external HTTP boundary, mocked in tests | Accepted | REQ-F1, REQ-N2 |
| [0003](0003-prompt-policy-split.md) | Split global policy from task-specific prompt content | Accepted | REQ-F6, REQ-F10, REQ-N7 |
| [0004](0004-docker-only-runtime.md) | Docker-only runtime; no host PHP/Composer | Accepted | REQ-N5 |
| [0005](0005-spec-aligned-content-retrieval.md) | Authoritative, cheapest-first specification retrieval | Accepted | REQ-N1 |

## Writing a new ADR

1. Copy the structure of an existing record. Number sequentially (`NNNN-kebab-title.md`).
2. Fill **Context / Decision / Consequences**; set status `Proposed`.
3. Cite the `REQ-#`(s) it serves and add a row to this index and to
   [traceability.md](../traceability.md).
4. On merge, set status to `Accepted`.
