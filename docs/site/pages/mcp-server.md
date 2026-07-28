# MCP Server

The **openEHR Assistant MCP Server** exposes openEHR domain knowledge to AI
assistants over the [Model Context Protocol](https://modelcontextprotocol.io/).
It helps agents discover, explain, design, and review openEHR artefacts. It is a
*knowledge and authoring-assistance* server — not a clinical data repository.

!!! note "Pre-release"
    Expect frequent updates and breaking changes until version 1.0.

## Hosted endpoint

| | |
|---|---|
| **URL** | `https://openehr-assistant-mcp.apps.cadasto.com/` |
| **Transport** | `streamable-http` |

See [Install](install.md) for local Docker, stdio, and per-client configuration.

## What it provides

### Tools

| Domain | Tools |
|--------|-------|
| CKM | `ckm_archetype_search`, `ckm_archetype_get`, `ckm_template_search`, `ckm_template_get` |
| Guides | `guide_search`, `guide_get`, `guide_adl_idiom_lookup` |
| Examples | `examples_search`, `examples_get` |
| Terminology | `terminology_resolve` |
| Type specs | `type_specification_search`, `type_specification_get` |

### Prompts

Guided prompts cover CKM exploration, type specs, terminology, guides,
explanation (archetype, template, AQL, simplified formats), ADL syntax fixing,
translation, and design/review workflows.

### Resources

Stable `openehr://` URIs for guides, examples, BMM type specifications, and
terminology — fetchable by MCP clients without tool calls.

### Completions

Argument suggestions for guide names, example names, and specification components.

## Pair with the plugin

The server supplies tools and knowledge. The
[openEHR Assistant Plugin](plugin.md) adds skills, commands, and agents that
orchestrate those tools for clinical modelling workflows.

## Full reference

- [GitHub README](https://github.com/cadasto/openehr-assistant-mcp#available-mcp-elements) — complete tool, prompt, and resource inventory
- [Contributor docs](https://github.com/cadasto/openehr-assistant-mcp/tree/main/docs) — SDD specs, development, testing
