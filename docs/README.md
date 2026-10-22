---
kind: guide
---

# openEHR Assistant MCP Server documentation

This page indexes the openEHR Assistant MCP Server's documentation for contributors
and maintainers: the Specification-Driven Development (SDD) spec set, the process
docs, and the operational guides. To connect an MCP client to the server, go
straight to [install.md](install.md).

The project follows SDD on the sdd plugin's `formal` profile: specifications are
first-class, version-controlled artefacts, and a **traceability chain** links every
requirement to its normative specification section, the code and tests that realise
it, and the decisions that shaped it. The chain is the contract; change it top-down.

```text
requirements/  →  specifications/  →  src/  →  tests/        (forward)
 (REQ-F/N-NNN)      (SPEC-* §N)       (code)  (verification)
      └──────────────┴──────── decisions/ (ADRs) ────────────┘
             specifications/traceability.yaml ties them together
```

## The SDD spec set

| Document | Role | Answers |
|----------|------|---------|
| [requirements/](requirements/README.md) | The **what**: one file per `REQ-F-NNN` functional and `REQ-N-NNN` non-functional requirement, with a generated index | "What must the server do?" |
| [specifications/](specifications/README.md) | The **how it must behave**: RFC-2119 topic specs, one canonical `§` per requirement | "What exactly is binding?" |
| [decisions/](decisions/README.md) | The **why**: Architecture Decision Records (MADR-lite) | "Why was it built this way?" |
| [traceability.yaml](specifications/traceability.yaml) | The **links**: REQ → spec § → code → tests, validated against the tree by `make spec-check` | "Where is REQ-X implemented and tested? Has the chain drifted?" |
| [.sdd.yaml](.sdd.yaml) | The **descriptor**: this repo's SDD conventions (profile, id style, paths, ground-truth source, gate) | "How is SDD configured here?" |

For one requirement's whole chain, run `python3 scripts/sdd-check.py context REQ-F-001`
in the dev container (`make sh`), or `/sdd-trace REQ-F-001` with the sdd plugin.

## Process docs

| Document | Role |
|----------|------|
| [development-process.md](development-process.md) | The SDD process: document kinds, the flow, lanes, the PR body |
| [ai-workflow.md](ai-workflow.md) | The agent loop and the `/sdd-*` skills that run it |
| [ci.md](ci.md) | What PR validation enforces, and how it maps to `make` targets |

## Operational docs

| Document | Role |
|----------|------|
| [architecture.md](architecture.md) | Component map: where each capability lives in the code, and the requirements it serves |
| [install.md](install.md) | Hosted and local setup, MCP client configurations (user-facing) |
| [development.md](development.md) | Docker dev environment, transports, configuration, the discovery-cache gotcha |
| [conventions.md](conventions.md) | Coding standard (PSR-12) and MCP authoring conventions |
| [testing.md](testing.md) | PHPUnit, PHPStan, coverage, MCP conformance, the SDD drift gate |

## How to change the system (SDD flow)

1. **Requirement**: add or edit a `REQ-*` file under [requirements/](requirements/README.md) (`/sdd-specify`).
2. **Specification**: state the binding behaviour in its canonical `§` under
   [specifications/](specifications/README.md); if the choice is irreversible, write an
   [ADR](decisions/README.md).
3. **Implement**: code under `src/`; update [architecture.md](architecture.md) when a
   component moves.
4. **Verify**: add or extend the mirrored `tests/…/*Test`.
5. **Trace**: update the record in [traceability.yaml](specifications/traceability.yaml),
   run `python3 scripts/sdd-check.py generate --root .` in the dev container to refresh
   the indexes, and run `make spec-check` (part of `make ci`) to confirm the chain still
   resolves.

## Related

- [AGENTS.md](../AGENTS.md): canonical conventions for AI agents and contributors
  (this `docs/` tree is its spec source of truth).
- [README.md](../README.md): product feature overview.
- [openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin):
  maintainer tooling for authoring tools/prompts/guides/examples in this repo.
- [openehr-assistant plugin](https://github.com/cadasto/openehr-assistant-plugin):
  the user-facing plugin that wraps this server for clinical end users.
