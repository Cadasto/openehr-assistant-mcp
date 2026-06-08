# Documentation — openEHR Assistant MCP Server

This project follows a lightweight **Specification-Driven Development (SDD)**
paradigm: specifications are first-class, version-controlled artefacts, and a
**traceability chain** links every requirement to its design, code, test, and
the decisions that shaped it. The chain is the contract — change it top-down.

```
requirements.md  →  architecture.md  →  src/  →  tests/        (forward)
   (REQ-#)            (design)        (code)  (verification)
        └───────────────┴──────── decisions/ (ADRs) ───────────┘
                         traceability.md ties them together
```

## The SDD spec set

| Document | Role | Answers |
|----------|------|---------|
| [requirements.md](requirements.md) | The **what** — `REQ-F#` functional + `REQ-N#` non-functional requirements | "What must the server do?" |
| [architecture.md](architecture.md) | The **how** — components mapped to the requirements they satisfy | "How is it built?" |
| [decisions/](decisions/) | **Why** — Architecture Decision Records (MADR-lite) | "Why was it built this way?" |
| [traceability.md](traceability.md) | The **links** — REQ ↔ code ↔ test ↔ ADR matrix | "Where is REQ-X implemented and tested?" |

## Operational docs

| Document | Role |
|----------|------|
| [install.md](install.md) | Hosted & local setup, MCP client configurations (user-facing) |
| [development.md](development.md) | Docker dev environment, transports, configuration, the discovery-cache gotcha |
| [conventions.md](conventions.md) | Coding standard (PSR-12) and MCP authoring conventions |
| [testing.md](testing.md) | PHPUnit, PHPStan, coverage, MCP conformance |

## How to change the system (SDD flow)

1. **Requirement** — add/edit a `REQ-#` in [requirements.md](requirements.md).
2. **Design** — update [architecture.md](architecture.md); if the choice is
   architecturally significant, write an [ADR](decisions/).
3. **Implement** — code under `src/`.
4. **Verify** — add/extend the mirrored `tests/…/*Test`.
5. **Trace** — update the [traceability matrix](traceability.md).

## Related

- [AGENTS.md](../AGENTS.md) — canonical conventions for AI agents and contributors
  (this `docs/` tree is its spec source of truth).
- [README.md](../README.md) — product feature overview.
- [openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin)
  — maintainer tooling for authoring tools/prompts/guides/examples in this repo.
- [openehr-assistant plugin](https://github.com/cadasto/openehr-assistant-plugin)
  — the user-facing plugin that wraps this server for clinical end users.
