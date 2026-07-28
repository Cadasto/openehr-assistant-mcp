# Plugin

The **openEHR Assistant Plugin** is the user-facing workflow layer for
[Claude Code](https://claude.ai/code) and [Cursor](https://cursor.com). It
works with the [MCP Server](mcp-server.md), which provides tools, prompts, and
resources (CKM, guides, terminology, type specs).

## What the plugin adds

| Layer | Role |
|-------|------|
| **Skills** | Guide-first workflows for archetypes, templates, AQL, compositions, demographics |
| **Commands** | Explicit one-shots: CKM search, explain, semantic diff, syntax fix, template-from-form |
| **Agents** | `clinical-modeler`, `ckm-scout`, `spec-researcher` for delegated tasks |
| **Hooks & rules** | Session context and openEHR modelling guardrails |

### Skills

| Skill | Purpose |
|-------|---------|
| `openehr-assistant` | Route any openEHR task; load guides before answering |
| `archetype-authoring` | Create, edit, review, translate archetypes |
| `archetype-lint` | Normative lint rules (STRICT / PERMISSIVE) |
| `template-authoring` | Template design with CGEM framework |
| `composition-builder` | FLAT, STRUCTURED, CANONICAL instances |
| `aql-authoring` | Query authoring and explanation |
| `demographic-modeling` | PARTY hierarchy and identity patterns |

## Install

**Claude Code** — from the Cadasto marketplace:

```
/plugin marketplace add cadasto/plugin-marketplace
/plugin install openehr-assistant@cadasto
```

**Cursor** — add the repository via Settings → Plugins (Git URL or local path).

Full install, update, MCP permissions, and Cursor details:

**[Plugin install guide →](https://github.com/cadasto/openehr-assistant-plugin/blob/main/docs/install.md)**

## MCP wiring

The plugin bundles a `.mcp.json` pointing at the hosted MCP server. Override it
for a local or stdio server — see [Install](install.md) on this site.

## Maintainer tooling

Building MCP tools, guides, or examples? That is the separate
[openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin)
— not the user-facing plugin documented here.

## Repository

[github.com/cadasto/openehr-assistant-plugin](https://github.com/cadasto/openehr-assistant-plugin)
