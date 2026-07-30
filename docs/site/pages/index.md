---
hide:
  - navigation
  - toc
template: home.html
---

<div class="home-hero" markdown="1">

![](assets/logo.svg){ .home-hero__mark }

# openEHR Assistant

<p class="home-tagline">
AI-native tooling for openEHR — archetypes, templates, AQL, terminology, and specifications through the Model Context Protocol.
</p>

<div class="home-cta" markdown="1">

[Get started](install.md){ .md-button .md-button--primary }
[View on GitHub](https://github.com/cadasto/openehr-assistant-mcp){ .md-button }

</div>

</div>

<h2 class="section-title">Why openEHR Assistant?</h2>

<div class="features-grid" markdown="1">

<div class="feature-card" markdown="1">

:material-toolbox:

### MCP-native tools
Search and retrieval for CKM, guides, examples, terminology, and type specifications — ready for any MCP client.

</div>

<div class="feature-card" markdown="1">

:material-book-open-variant:

### Guide-first workflows
Bundled implementation guides ground each answer; task prompts orchestrate multi-step modelling and review.

</div>

<div class="feature-card" markdown="1">

:material-database-search:

### CKM integration
Search and retrieve archetypes and templates from the Clinical Knowledge Manager with relevance scoring.

</div>

<div class="feature-card" markdown="1">

:material-shield-check:

### Spec-aligned content
Type specs, digests, and terminology are grounded in authoritative openEHR sources — not model memory.

</div>

<div class="feature-card" markdown="1">

:material-server-network:

### Hosted or self-hosted
Use the Cadasto-hosted endpoint or run your own instance over streamable HTTP or stdio.

</div>

<div class="feature-card" markdown="1">

:material-puzzle:

### Plugin skills layer
Pair the server with the user-facing plugin for skills, commands, and agents that guide clinical modelling.

</div>

</div>

<h2 class="section-title">Two parts, one workflow</h2>

<div class="two-products" markdown="1">

<div class="product-card" markdown="1">

:material-server:

### MCP Server
The knowledge and tooling layer: tools, prompts, resources, and completions. Connect once from Claude Desktop, Cursor, LibreChat, or any MCP client.

[Server overview](mcp-server.md){ .md-button }

</div>

<div class="product-card" markdown="1">

:material-toy-brick:

### Plugin
The workflow layer for Claude Code and Cursor: skills, slash commands, and subagents that know when to load guides and call MCP tools.

[Plugin overview](plugin.md){ .md-button }

</div>

</div>

<div class="quick-start" markdown="1">

## Quick Start

Point your MCP client at the hosted server:

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

[Full install options →](install.md)

</div>
