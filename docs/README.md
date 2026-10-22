---
kind: guide
---

# openEHR Assistant MCP Server documentation

This page indexes the openEHR Assistant MCP Server's documentation for contributors
and maintainers: the SDD knowledge base, the process docs, and the operational
guides. To connect an MCP client to the server, go straight to
[install.md](install.md).

The project follows Specification-Driven Development on the sdd plugin's
**informative** profile: the **code leads**, `docs/` is a knowledge base that
describes the code as it is, and one document, the
[constitution](architecture.md), binds every change. A change that alters
behaviour updates the documents that describe it in the same pull request.

```text
            architecture.md  (constitution: the MUST rules every change respects)
                   │
src/ + tests/  ──▶ components.md · requirements.md · guides   (describe the code as it is)
                   │
            decisions/  (ADRs: why it is built this way)
```

## The SDD knowledge base

| Document | Role | Answers |
|----------|------|---------|
| [architecture.md](architecture.md) | The **constitution**: the only binding document; its MUST sentences are what every change respects | "What must never break?" |
| [components.md](components.md) | The **component map**: where each capability lives in the code | "How is it built?" |
| [requirements.md](requirements.md) | The **capability catalogue**: `REQ-F#` functional and `REQ-N#` non-functional capabilities, as shipped | "What does the server do, and why?" |
| [decisions/](decisions/README.md) | The **why**: Architecture Decision Records (MADR-lite) | "Why was it built this way?" |
| [.sdd.yaml](.sdd.yaml) | The **descriptor**: this repo's SDD settings (profile, constitution path, ground-truth source, gate) | "How is SDD configured here?" |

## Process docs

| Document | Role |
|----------|------|
| [development-process.md](development-process.md) | How a change flows on the informative profile, and the PR body |
| [ai-workflow.md](ai-workflow.md) | The agent loop and the `/sdd-*` skills that run it |
| [ci.md](ci.md) | What PR validation enforces, and how it maps to `make` targets |

## Operational docs

| Document | Role |
|----------|------|
| [install.md](install.md) | Hosted and local setup, MCP client configurations (user-facing) |
| [development.md](development.md) | Docker dev environment, transports, configuration, the discovery-cache gotcha |
| [conventions.md](conventions.md) | Coding standard (PSR-12) and MCP authoring conventions |
| [testing.md](testing.md) | PHPUnit, PHPStan, coverage, MCP conformance, the docs drift gate |

## How to change the system

1. **Check the constitution**: the change must keep every MUST in
   [architecture.md](architecture.md). Changing one of them is a deliberate
   amendment through `/sdd-specify`, with the reason in the commit body or an
   [ADR](decisions/README.md).
2. **Implement and test**: code under `src/`, its mirrored `tests/…/*Test`.
3. **Describe**: update the pages that describe what changed
   ([components.md](components.md), [requirements.md](requirements.md), a guide),
   and write an ADR if the choice is irreversible.
4. **Check**: run `make ci`, which includes `make spec-check`, the docs drift gate.

## Related

- [AGENTS.md](../AGENTS.md): canonical conventions for AI agents and contributors.
- [README.md](../README.md): product feature overview.
- [openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin):
  maintainer tooling for authoring tools/prompts/guides/examples in this repo.
- [openehr-assistant plugin](https://github.com/cadasto/openehr-assistant-plugin):
  the user-facing plugin that wraps this server for clinical end users.
