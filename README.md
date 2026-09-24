# openEHR Assistant MCP Server

[![PR validation](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/pr-validation.yml/badge.svg)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/pr-validation.yml)
[![Release Docker image (GHCR)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/release.yml/badge.svg)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/release.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Version](https://img.shields.io/badge/version-0.20.0-blue)](CHANGELOG.md)
[![PHP Version](https://img.shields.io/badge/php-8.4-blue.svg)](https://www.php.net/)
[![MCP](https://img.shields.io/badge/MCP-Model%20Context%20Protocol-orange.svg)](https://modelcontextprotocol.io/)
[![openEHR](https://img.shields.io/badge/openEHR-compatible-009688)](https://openehr.org)
[![Keep a Changelog](https://img.shields.io/badge/Keep%20a%20Changelog-1.1.0-E05735)](CHANGELOG.md)

An [MCP](https://modelcontextprotocol.io/) server that helps AI assistants work with [openEHR](https://openehr.org/) archetypes, templates, AQL, terminology, and specifications. It is for people who connect an MCP client (Claude Desktop, Cursor, LibreChat, …) to their openEHR work. Working with openEHR means navigating the [Clinical Knowledge Manager (CKM)](https://ckm.openehr.org/), [intricate type systems](https://specifications.openehr.org/), and ADL syntax rules. The server gives the client direct access to those sources through MCP tools, prompts, resources, and completions, so the assistant can help with archetype exploration, semantic explanation, language translation, syntax correction, and design reviews.

The server owns the MCP surface: the tools, prompts, and resources, and the CKM access, guides, examples, terminology, and type specifications behind them. It does not decide when an assistant should use them. That workflow layer is the [openEHR Assistant Plugin](https://github.com/cadasto/openehr-assistant-plugin), which adds skills, commands, agents, and hooks for Claude Code and Cursor; pair the two for guided openEHR workflows. Claude Code users can install the plugin from the [Cadasto Plugin Marketplace](https://github.com/cadasto/plugin-marketplace).

**Requirements.** An MCP client that speaks the `streamable-http` or `stdio` transport. The hosted endpoint needs no install, only network access to `https://openehr-assistant-mcp.apps.cadasto.com/`. To run your own instance you need Docker with Docker Compose (and Git to clone the repository), or Docker alone to run the published image over stdio. PHP 8.4 ships inside the image, so the host needs no PHP. The CKM tools call the CKM REST API (`https://ckm.openehr.org/ckm/rest` by default, set with `CKM_API_BASE_URL`); guides, examples, terminology, and type specifications are bundled with the server.

> **Pre-release:** expect frequent updates and breaking changes until version 1.0.

## Table of contents

- [Features](#features)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Available MCP elements](#available-mcp-elements)
- [Development](#development)
- [Documentation](#documentation)
- [Acknowledgements](#acknowledgements)
- [License](#license)

## Features

- Works with any MCP client (Claude Desktop, Cursor, LibreChat, …).
- Tools, prompts, resources, and completions for openEHR archetypes, templates, AQL, terminology, and specifications.
- Guided prompts orchestrate multi-step modelling and review workflows.
- Use the hosted endpoint, or run it locally over streamable HTTP or stdio.

## Installation

[docs/install.md](docs/install.md) covers each option, with per-client setup for Claude Desktop, LibreChat, Cursor, and IntelliJ Junie:

- **Hosted endpoint**: no install; point your client at the URL in the [Quick start](#quick-start).
- **Local Docker instance**: clone the repository and start the stack; it serves `streamable-http` on `http://localhost:8343/`.
- **stdio**: run `php public/index.php --transport=stdio` in the dev container, or from the published image `ghcr.io/cadasto/openehr-assistant-mcp:latest`.

The environment variables, including the CKM base URL, the HTTP timeout, and the `MCP_ALLOWED_HOSTS` list you set when deploying behind a reverse proxy, are listed under [Configuration](docs/development.md#configuration).

## Quick start

Point your MCP client at the hosted endpoint:

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

To run your own instance (Docker or stdio) and for per-client setup, see [docs/install.md](docs/install.md).

## Available MCP elements

### Tools

CKM (Clinical Knowledge Manager):

- `ckm_archetype_search`: list archetypes from CKM matching search criteria
- `ckm_archetype_get`: get a CKM archetype by its identifier
- `ckm_template_search`: list templates (OET/OPT) from CKM matching search criteria
- `ckm_template_get`: get a CKM template (OET/OPT) by its identifier

openEHR terminology:

- `terminology_resolve`: resolve a terminology concept ID to its rubric, or find the ID for a given rubric across groups

Guides (model-reachable):

- `guide_search`: search bundled guides and return short snippets with canonical `openehr://guides` URIs
- `guide_get`: retrieve full guide content by URI or (category, name)
- `guide_adl_idiom_lookup`: look up targeted ADL idiom snippets for common modelling patterns

Examples (curated artefacts):

- `examples_search`: search bundled examples (AQL, FLAT/STRUCTURED payloads, ADL archetypes) and return snippets with `openehr://examples` URIs
- `examples_get`: retrieve an example by URI or (kind, name)

openEHR type specifications:

- `type_specification_search`: list bundled openEHR type specifications matching search criteria
- `type_specification_get`: retrieve an openEHR type specification (as BMM JSON)

### Prompts

Optional prompts that guide AI assistants through common openEHR and CKM workflows using the tools above:

- `ckm_explorer`: discover and fetch CKM archetype (ADL/XML/Mindmap) or template (OET/OPT) definitions
- `type_specification_explorer`: discover and fetch openEHR type specifications (BMM JSON)
- `terminology_explorer`: discover and retrieve openEHR terminology (groups and codesets)
- `guide_explorer`: discover and retrieve openEHR implementation guides
- `explain_archetype`: explain an archetype's semantics (audiences, elements, constraints)
- `explain_template`: explain openEHR template semantics
- `explain_aql`: explain an AQL query's intent, structure, and semantics
- `explain_simplified_format`: explain the context, paths, and data elements of a FLAT/STRUCTURED payload
- `translate_archetype_language`: translate an archetype's terminology section between languages, with safety checks
- `fix_adl_syntax`: correct or improve ADL syntax without changing semantics; returns before/after and notes
- `design_or_review_archetype`: design or review an archetype for a concept or RM class, with structured output
- `design_or_review_template`: design or review an openEHR template (OET)
- `design_or_review_aql`: design or review an AQL query, using the AQL guides
- `design_or_review_simplified_format`: design or review a FLAT/STRUCTURED instance, using the Simplified Formats guides

### Completion providers

Parameter suggestions in MCP clients when invoking tools or resources:

- `Guides`: guide `{name}` values per category (`openehr://guides/{category}/{name}`)
- `Examples`: example `{name}` values per kind (`openehr://examples/{kind}/{name}`)
- `SpecificationComponents`: `{component}` values from `resources/bmm` (`openehr://spec/type/{component}/{name}`)

### Resources

Exposed with `#[McpResourceTemplate]` and `#[McpResource]`, and fetchable by clients through `openehr://…` URIs:

- **Guides**: `openehr://guides/{category}/{name}` (Markdown). Categories: `archetypes`, `templates`, `aql`, `simplified_formats`, `specs` (per-document spec digests), `howto` (toolchain how-tos). Retrieve with `guide_search` / `guide_get`.
  - For example `openehr://guides/aql/principles`, `openehr://guides/specs/rm-ehr`, `openehr://guides/howto/spec-lookup`
- **Examples**: `openehr://examples/{kind}/{name}`. Kinds: `aql`, `flat`, `structured` (Markdown: metadata header and fenced code block), `archetypes` (native `.adl`, `text/plain`). Retrieve with `examples_search` / `examples_get`.
  - For example `openehr://examples/aql/latest_blood_pressure_per_ehr`, `openehr://examples/archetypes/openEHR-EHR-OBSERVATION.blood_pressure.v2`
- **Type specifications**: `openehr://spec/type/{component}/{name}` (BMM JSON).
  - For example `openehr://spec/type/RM/COMPOSITION`, `openehr://spec/type/AM/ARCHETYPE`
- **Terminology**: `openehr://terminology` (JSON): all openEHR terminology groups and codesets.

## Development

The runtime is Docker-only: there is no host PHP or Composer, and every `php`, `composer`, and `vendor/bin/*` command runs inside the `app` dev container.

```bash
cp .env.example .env
make up-dev      # start dev containers
make install     # install Composer dependencies in the container
make ci          # spec-check + PHPStan + tests
```

[docs/development.md](docs/development.md) covers the dev environment and the MCP Inspector, [docs/testing.md](docs/testing.md) the test and validation workflow, and [CONTRIBUTING.md](CONTRIBUTING.md) the contribution process; contributions are welcome. Notable changes are recorded in [CHANGELOG.md](CHANGELOG.md). Maintainers working on this repository with Claude Code or Cursor can install the [openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin) for authoring and release tooling.

## Documentation

- [docs/install.md](docs/install.md): hosted and local setup, client configurations
- [docs/development.md](docs/development.md): Docker dev environment, Makefile, configuration, MCP Inspector
- [docs/conventions.md](docs/conventions.md): coding standard and MCP authoring conventions
- [docs/testing.md](docs/testing.md): tests, static analysis, MCP conformance
- [docs/](docs/README.md): the Specification-Driven Development spec set (requirements, architecture, decisions, traceability)
- [CONTRIBUTING.md](CONTRIBUTING.md): how to contribute
- [AGENTS.md](AGENTS.md): repository instructions for AI coding agents

## Acknowledgements

This project is inspired by and grateful to:

- The original [Python openEHR MCP Server](https://github.com/deak-ai/openehr-mcp-server).
- [Seref Arikan](https://www.linkedin.com/in/seref-arikan/) and [Sidharth Ramesh](https://www.linkedin.com/in/sidharthramesh1/), for inspiration on MCP integration.
- The [PHP MCP Server framework](https://github.com/modelcontextprotocol/php-sdk).
- [Ocean Health Systems](https://oceanhealthsystems.com/) for the Clinical Knowledge Manager (CKM), an essential tool for the openEHR community that enables collaborative development and sharing of archetypes and templates.
- [freshEHR](https://www.freshehr.com/) for the CGEM framework (Contextual situation, Global background, Event assessment, Managed response), which informs our template-design guides (CC-BY).
- [Silje Ljosland Bakke](https://github.com/siljelb), for contributions to the archetype and language related guides.

## License

MIT. See [LICENSE](LICENSE).
