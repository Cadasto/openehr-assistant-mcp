# The openEHR Assistant MCP Server

[![PR validation](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/pr-validation.yml/badge.svg)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/pr-validation.yml)
[![Release Docker image (GHCR)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/release.yml/badge.svg)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/release.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-8.4-blue.svg)](https://www.php.net/)
[![MCP](https://img.shields.io/badge/MCP-Model%20Context%20Protocol-orange.svg)](https://modelcontextprotocol.io/)

An [MCP](https://modelcontextprotocol.io/) server that helps AI assistants work with [openEHR](https://openehr.org/) — archetypes, templates, AQL, terminology, and specifications.

Working with openEHR means navigating the [Clinical Knowledge Manager (CKM)](https://ckm.openehr.org/), [intricate type systems](https://specifications.openehr.org/), and ADL syntax rules. This server gives MCP clients (Claude Desktop, Cursor, LibreChat, …) direct access to those resources so assistants can help with archetype exploration, semantic explanation, language translation, syntax correction, and design reviews.

> **Pre-release:** expect frequent updates and breaking changes until version 1.0.

> **Recommended:** pair this server with the [openEHR Assistant Plugin](https://github.com/cadasto/openehr-assistant-plugin) — skills, prompts, and agents that guide assistants through openEHR workflows. Claude Code users can install it from the [Cadasto Plugin Marketplace](https://github.com/cadasto/plugin-marketplace).

## Table of Contents

- [Features](#features)
- [Quick Start](#quick-start)
- [Available MCP Elements](#available-mcp-elements)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Acknowledgments](#acknowledgments)

----

## Features

- Works with any MCP client (Claude Desktop, Cursor, LibreChat, …).
- Tools, prompts, resources, and completions for openEHR archetypes, templates, AQL, terminology, and specifications.
- Guided prompts orchestrate multi-step modelling and review workflows.
- Use the hosted endpoint, or run locally (transports: streamable HTTP and stdio).

----

## Quick Start

The quickest path is the **hosted endpoint** — point your MCP client at it:

| | |
|---|---|
| **URL** | `https://openehr-assistant-mcp.apps.cadasto.com/` |
| **Transport** | `streamable-http` |

```json
{
  "mcpServers": {
    "openehr-assistant-mcp": {
      "type": "streamable-http",
      "url": "https://openehr-assistant-mcp.apps.cadasto.com/"
    }
  }
}
```

To run your own instance (Docker or stdio) and for per-client setup (Claude Desktop, LibreChat, Cursor, Junie), see **[docs/install.md](docs/install.md)**.

----

## Available MCP Elements

### Tools

CKM (Clinical Knowledge Manager)
- `ckm_archetype_search` — List Archetypes from CKM matching search criteria
- `ckm_archetype_get` — Get a CKM Archetype by its identifier
- `ckm_template_search` — List Templates (OET/OPT) from CKM matching search criteria
- `ckm_template_get` — Get a CKM Template (OET/OPT) by its identifier

openEHR Terminology
- `terminology_resolve` — Resolve a terminology concept ID to its rubric, or find the ID for a given rubric across groups

Guides (model-reachable)
- `guide_search` — Search bundled guides and return short snippets with canonical `openehr://guides` URIs
- `guide_get` — Retrieve guide content by URI or (category, name), chunked by default
- `guide_adl_idiom_lookup` — Look up targeted ADL idiom snippets for common modelling patterns

Examples (curated artefacts)
- `examples_search` — Search bundled examples (AQL, FLAT/STRUCTURED payloads, ADL archetypes) and return snippets with `openehr://examples` URIs
- `examples_get` — Retrieve an example by URI or (kind, name)

openEHR Type specification
- `type_specification_search` — List bundled openEHR Type specifications matching search criteria
- `type_specification_get` — Retrieve an openEHR Type specification (as BMM JSON)

### Prompts

Optional prompts that guide AI assistants through common openEHR and CKM workflows using the tools above.
- `ckm_explorer` — Discover and fetch CKM Archetype (ADL/XML/Mindmap) or Template (OET/OPT) definitions
- `type_specification_explorer` — Discover and fetch openEHR Type specifications (BMM JSON)
- `terminology_explorer` — Discover and retrieve openEHR terminology (groups and codesets)
- `guide_explorer` — Discover and retrieve openEHR implementation guides
- `explain_archetype` — Explain an archetype's semantics (audiences, elements, constraints)
- `explain_template` — Explain openEHR Template semantics
- `explain_aql` — Explain an AQL query's intent, structure, and semantics
- `explain_simplified_format` — Explain context, paths, and data elements of a FLAT/STRUCTURED payload
- `translate_archetype_language` — Translate an archetype's terminology section between languages with safety checks
- `fix_adl_syntax` — Correct or improve ADL syntax without changing semantics; before/after + notes
- `design_or_review_archetype` — Design or review an archetype for a concept/RM class, with structured output
- `design_or_review_template` — Design or review an openEHR Template (OET)
- `design_or_review_aql` — Design or review an AQL query, using the AQL guides
- `design_or_review_simplified_format` — Design or review a FLAT/STRUCTURED instance, using the Simplified Formats guides

### Completion Providers

Parameter suggestions in MCP clients when invoking tools or resources.
- `Guides` — guide `{name}` values per category (`openehr://guides/{category}/{name}`)
- `Examples` — example `{name}` values per kind (`openehr://examples/{kind}/{name}`)
- `SpecificationComponents` — `{component}` values from `resources/bmm` (`openehr://spec/type/{component}/{name}`)

### Resources

Exposed via `#[McpResource]` and fetchable by clients using `openehr://…` URIs.

- **Guides** — `openehr://guides/{category}/{name}` (Markdown). Categories: `archetypes`, `templates`, `aql`, `simplified_formats`, `specs` (per-document spec digests), `howto` (toolchain how-tos). Retrieve via `guide_search` / `guide_get`.
  - e.g. `openehr://guides/aql/principles`, `openehr://guides/specs/rm-ehr`, `openehr://guides/howto/spec-lookup`
- **Examples** — `openehr://examples/{kind}/{name}`. Kinds: `aql`, `flat`, `structured` (Markdown: metadata header + fenced code block), `archetypes` (native `.adl`, `text/plain`). Retrieve via `examples_search` / `examples_get`.
  - e.g. `openehr://examples/aql/latest_blood_pressure_per_ehr`, `openehr://examples/archetypes/openEHR-EHR-OBSERVATION.blood_pressure.v2`
- **Type Specifications** — `openehr://spec/type/{component}/{name}` (BMM JSON).
  - e.g. `openehr://spec/type/RM/COMPOSITION`, `openehr://spec/type/AM/ARCHETYPE`
- **Terminology** — `openehr://terminology` (JSON): all openEHR terminology groups and codesets.

----

## Documentation

- **[docs/install.md](docs/install.md)** — hosted & local setup, client configurations
- **[docs/development.md](docs/development.md)** — Docker dev environment, Makefile, configuration, MCP Inspector
- **[docs/conventions.md](docs/conventions.md)** — coding standard and MCP authoring conventions
- **[docs/testing.md](docs/testing.md)** — tests, static analysis, MCP conformance
- **[docs/](docs/README.md)** — the Specification-Driven Development spec set (requirements, architecture, decisions, traceability)
- **[CONTRIBUTING.md](CONTRIBUTING.md)** — how to contribute · **[AGENTS.md](AGENTS.md)** — repository instructions for AI coding agents

----

## Contributing

Contributions are welcome — see **[CONTRIBUTING.md](CONTRIBUTING.md)** for setup, conventions, and how to propose changes, and **[CHANGELOG.md](CHANGELOG.md)** for notable changes.

**License:** MIT — see [LICENSE](LICENSE).

----

## Acknowledgments

This project is inspired by and grateful to:
- The original Python openEHR MCP Server: https://github.com/deak-ai/openehr-mcp-server
- [Seref Arikan](https://www.linkedin.com/in/seref-arikan/), [Sidharth Ramesh](https://www.linkedin.com/in/sidharthramesh1/) — for inspiration on MCP integration
- The PHP MCP Server framework: https://github.com/modelcontextprotocol/php-sdk
- [Ocean Health Systems](https://oceanhealthsystems.com/) for the Clinical Knowledge Manager (CKM), an essential tool for the openEHR community that enables collaborative development and sharing of archetypes and templates.
- [freshEHR](https://www.freshehr.com/) for the CGEM framework (Contextual situation, Global background, Event assessment, Managed response), which informs our template-design guides (CC-BY).
- [Silje Ljosland Bakke](https://github.com/siljelb) — for contributions to the archetype and language related guides.
