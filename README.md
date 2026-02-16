# The openEHR Assistant MCP Server

[![PR validation](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/pr-validation.yml/badge.svg)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/pr-validation.yml)
[![Release Docker image (GHCR)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/release.yml/badge.svg)](https://github.com/cadasto/openehr-assistant-mcp/actions/workflows/release.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-8.4-blue.svg)](https://www.php.net/)
[![MCP](https://img.shields.io/badge/MCP-Model%20Context%20Protocol-orange.svg)](https://modelcontextprotocol.io/)

The MCP Server to assist end-user on various [openEHR](https://openehr.org/) related tasks and APIs.

The [Model Context Protocol (MCP)](https://modelcontextprotocol.io/docs/getting-started/intro) is an open standard that enables AI assistants to connect to external data sources and tools in a secure and standardized way. MCP servers act as bridges between AI clients (like Claude Desktop, Cursor, or LibreChat) and domain-specific APIs, databases, or knowledge bases. 

The **openEHR Assistant MCP Server** brings this power to the healthcare informatics domain, specifically targeting openEHR modelers and developers. 
Working with openEHR archetypes, templates, and specifications often involves navigating complex APIs, searching through [Clinical Knowledge Manager (CKM)](https://ckm.openehr.org/) repositories, understanding [intricate type systems](https://specifications.openehr.org/), and ensuring compliance with ADL syntax rules. 
Many of these workflows, such as archetype design, template composition, terminology resolution, and syntax validation, are repetitive, time-consuming, and sometimes too complex to automate. 

This server augments these workflows by providing AI assistants with direct access to openEHR resources, terminology services, and CKM APIs, enabling them to assist with tasks like archetype exploration, semantic explanation, language translation, syntax correction, and design reviews. 

> NOTE:
> This project is currently in a pre-release state. Expect frequent updates and potential breaking changes to the architecture and feature set until version 1.0.

## Table of Contents

- [Features](#features)
- [Available MCP Elements](#available-mcp-elements)
- [Transports](#transports)
- [Quick Start](#quick-start)
- [Common client configurations](#common-client-configurations)
- [Development tips](#development-tips)
- [Contributing](#contributing)
- [Acknowledgments](#acknowledgments)

----

## Features

- Works with MCP clients such as Claude Desktop, Cursor, LibreChat, etc.
- Exposes tools for openEHR Archetypes and specifications.
- Guided Prompts help orchestrate multi-step workflows.
- Run remotely (endpoint URL: https://openehr-assistant-mcp.apps.cadasto.com/) or locally (transports: streamable HTTP and stdio)

### Implementation aspects 

- Made with PHP 8.4; PSR-compliant codebase
- Attribute-based MCP tool discovery (via https://github.com/mcp/sdk) with file-based cache
- Attribute-based MCP prompt discovery (seeded conversations for complex tasks) with file-based cache
- MCP Resource templates and Completion Providers for better UX in MCP clients
- Transports: streamable HTTP and stdio (for development)
- Docker images for production and development
- Structured logging with Monolog

----

## Available MCP Elements

### Tools

CKM (Clinical Knowledge Manager)
- `ckm_archetype_search` - List Archetypes from the CKM server matching search criteria
- `ckm_archetype_get` - Get a CKM Archetype by its identifier
- `ckm_template_search` - List Templates (OET/OPT) from the CKM server matching search criteria
- `ckm_template_get` - Get a CKM Template (OET/OPT) by its identifier

openEHR Terminology
- `terminology_resolve` - Resolve an openEHR terminology concept ID to its rubric, or find the ID for a given rubric across groups.

Guides (model-reachable)
- `guide_search` - Search bundled guides by query and return short snippets with canonical openehr://guides URIs.
- `guide_get` - Retrieve guide content by URI or (category, name) with chunked sections by default.
- `guide_adl_idiom_lookup` - Lookup targeted ADL idiom snippets from the cheatsheet for common modelling patterns.

openEHR Type specification
- `type_specification_search` - List bundled openEHR Type specifications matching search criteria.
- `type_specification_get` - Retrieve an openEHR Type specification (as BMM JSON).

### Prompts

Optional prompts that guide AI assistants through common openEHR and CKM workflows using the tools above.
- `ckm_archetype_explorer` - Explore CKM Archetypes by discovering and fetching definitions (ADL/XML/Mindmap), using `ckm_archetype_search` and `ckm_archetype_get` tools.
- `ckm_template_explorer` - Explore CKM Templates by discovering and fetching definitions (OET/OPT), using `ckm_template_search` and `ckm_template_get` tools.
- `type_specification_explorer` - Discover and fetch openEHR Type specifications (as BMM JSON) using `type_specification_search` and `type_specification_get` tools.
- `terminology_explorer` - Discover and retrieve openEHR terminology definitions (groups and codesets) using terminology resources.
- `guide_explorer` - Discover and retrieve openEHR implementation guides using `guide_search`, `guide_get`, and `guide_adl_idiom_lookup` tools.
- `explain_archetype` - Explain an archetype’s semantics (audiences, elements, constraints).
- `explain_template` - Explain openEHR Template semantics.
- `explain_aql` - Explain the intent, structure, and semantics of an AQL query (containment, archetype paths, filters, deployed OPT assumptions).
- `translate_archetype_language` - Translate an archetype’s terminology section between languages with safety checks.
- `fix_adl_syntax` - Correct or improve Archetype syntax without changing semantics; provides before/after and notes.
- `design_or_review_archetype` - Design or review task for a specific concept/RM class with structured outputs.
- `design_or_review_template` - Design or review task for an openEHR Template (OET).
- `design_or_review_aql` - Design or review task for an AQL query, using AQL guides (principles, syntax, idioms, checklist).

### Completion Providers

Completion providers supply parameter suggestions in MCP clients when invoking tools or resources.
- `Guides` - suggests guide `{name}` values for categories `archetypes`, `templates`, and `aql` (resource URI `openehr://guides/{category}/{name}`)
- `SpecificationComponents` - suggests `{component}` values based on directories in `resources/bmm`  resource URI

### Resources

MCP Server Resources are exposed via `#[McpResource]` annotated methods and can be fetched by MCP clients using `openehr://...` URIs. 
They are used to provide access to openEHR resources (guides, specifications, terminology) and to orchestrate complex workflows.

Guides (Markdown)
- URI template: `openehr://guides/{category}/{name}`
- On-disk mapping: `resources/guides/{category}/{name}.md`
- Model access: use `guide_search` and `guide_get` to retrieve guide content in short, task-relevant chunks.
- Examples:
  - `openehr://guides/archetypes/checklist`
  - `openehr://guides/archetypes/adl-syntax`
  - `openehr://guides/aql/principles`

Type Specifications (BMM JSON)
- URI template: `openehr://spec/type/{component}/{name}`
- On-disk mapping: `resources/bmm/{COMPONENT}/{NAME}.bmm.json`
- Examples:
  - `openehr://spec/type/RM/COMPOSITION`
  - `openehr://spec/type/AM/ARCHETYPE`
  - `openehr://spec/type/AM2/ARCHETYPE_HRID`

Terminologies (JSON)
- URI: `openehr://terminology` contains all terminology groups and codesets
- Provides access to both terminology groups (concepts/rubrics) and codesets.
- On-disk mapping: `resources/terminology/openehr_terminology.xml`

----

## Transports

MCP Transports are used to communicate with MCP clients. 

- `streamable-http` (default): HTTPS (port 443); dev setup exposes an additional HTTP port `8343` via Caddy.
- `stdio`: Suitable for process-based MCP clients, or for local development. 
  - Start option: pass `--transport=stdio` to `public/index.php`.

---

## Quick Start

To get started, use one of the following options:

1. **No local setup (fastest):** use our hosted endpoint.
2. **Local via Docker (recommended for contributors):** run the server with `docker compose`.
3. **Local via stdio:** run as a process for MCP clients that prefer stdio.

----

### Option 1: Use our hosted server (no install)

If you just want to use this MCP server with minimal setup, start here.

Use this MCP server URL directly in your client:

- **URL:** `https://openehr-assistant-mcp.apps.cadasto.com/`
- **Transport:** `streamable-http`

Example MCP config:

```json
{
  "mcpServers": {
    "openehr-assistant-remote": {
      "type": "streamable-http",
      "url": "https://openehr-assistant-mcp.apps.cadasto.com/"
    }
  }
}
```

See below for more [specific client configurations](#common-client-configurations).

----

### Option 2: Run locally with Docker (recommended for contributors)

Use this when editing tools/prompts/resources and wanting immediate feedback.

#### Prerequisites

- Docker + Docker Compose
- Git

#### 1) Clone the repository

```bash
git clone https://github.com/cadasto/openehr-assistant-mcp.git
cd openehr-assistant-mcp
```

#### 2) Prepare environment

```bash
cp .env.example .env
```

> Tip: Default values work for most users. You usually only need to edit `.env` if you want to change domain, logging, or CKM endpoint.

#### 3) Start dev containers

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build --force-recreate
# or
make up-dev
```

#### 4) Install Composer dependencies

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -u 1000:1000 mcp composer install
# or
make install
```

#### 5) Connect your MCP client

- Default local endpoint (streamable HTTP): `https://openehr-assistant-mcp.local/`; set also this name in your host file, asscociating it with `127.0.0.1 openehr-assistant-mcp.local`.
- Dev endpoint (with dev override): `http://localhost:8343/`

> If `openehr-assistant-mcp.local` does not resolve on your machine, use the dev setup below and connect to `http://localhost:8343/`.

Alternatively, use stdio by running a similar command to the following when you want your MCP client to launch the server process directly.

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp php public/index.php --transport=stdio
```

---

### Option 3: Run locally via stdio

Use stdio when your MCP client launches the server process directly.

Make sure your MCP client supports stdio transport and runs one of the following commands.

#### 1) From dev containers

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp php public/index.php --transport=stdio
```

#### 2) From a published Docker image

```bash
docker run --rm -i ghcr.io/cadasto/openehr-assistant-mcp:latest php public/index.php --transport=stdio
```

---

## Common client configurations

### Typical configuration

In most cases, add **one** of the following server configurations:

```json
{
  "mcpServers": {
    "openehr-assistant-mcp": {
      "type": "streamable-http",
      "url": "https://openehr-assistant-mcp.apps.cadasto.com/"
    },
    "openehr-assistant-mcp-stdio": {
      "command": "docker",
      "args": [
        "run", "-i", "--rm",
        "ghcr.io/cadasto/openehr-assistant-mcp:latest",
        "php", "public/index.php", "--transport=stdio"
      ]
    },
    "openehr-assistant-mcp-http": {
      "type": "streamable-http",
      "url": "http://host.docker.internal:8343/"
    }
  }
}
```

### Claude Desktop (`mcpServers`)

Add the remote URL `https://openehr-assistant-mcp.apps.cadasto.com/` in Menu > Settings > Connectors > Add custom connector.

Alternatively, use **Menu** → **Developer** → **Edit Config** to add one of the server configurations – see above.

### LibreChat (streamable HTTP)

```yaml
mcpServers:
  openehr-assistant-mcp:
    type: streamable-http
    url: http://host.docker.internal:8343/
```

### Cursor

1. Open **Cursor Settings** → **MCP**.
2. Add a new MCP server.
3. Choose one of these connection options:
   - **Hosted**: `type=streamable-http`, `url=https://openehr-assistant-mcp.apps.cadasto.com/`
   - **Local dev**: `type=streamable-http`, `url=http://host.docker.internal:8343/`
   - **Local stdio**: run with Docker – see above.

### IntelliJ Junie

1. Open **Settings/Preferences** → **Tools** → **Junie** → **MCP Servers** (wording may vary by version).
2. Add a server using either:
   - **Streamable HTTP** URL (`https://openehr-assistant-mcp.apps.cadasto.com/` or `http://host.docker.internal:8343/`), or
   - **Stdio command** (Docker command above).
3. Save configuration and refresh/restart Junie so tools are discovered.

---

## Development tips

### MCP Inspector

Run the MCP Inspector to inspect requests/responses and debug behavior:

```bash
make inspector
```

The terminal may show `http://0.0.0.0:6274/`; open it as `http://localhost:6274/` (or your machine IP) in your browser.

### Makefile shortcuts

- Build images: `make build` (prod) or `make build-dev` (dev)
- Start services: `make up` (prod) or `make up-dev` (dev override with live volume mounts)
- Prepare `.env`: `make env`
- Install dependencies in dev container: `make install`
- Tail logs: `make logs`
- Open shell in dev container: `make sh`
- Run MCP inspector: `make inspector`
- Show help: `make help`

### Environment Variables

- `APP_ENV`: application environment (`development`/`testing`/`production`). Default: `production`
- `LOG_LEVEL`: Monolog level (`debug`, `info`, `warning`, `error`, etc.). Default: `info`
- `CKM_API_BASE_URL`: base URL for the openEHR CKM REST API. Default: `https://ckm.openehr.org/ckm/rest`
- `HTTP_TIMEOUT`: HTTP client timeout in seconds (float). Default: `3.0`
- `HTTP_SSL_VERIFY`: set to `false` to disable verification or provide a CA bundle path. Default: `true`
- `XDG_DATA_HOME`: directory for application data, including cache and sessions. Default: `/tmp` (the app uses `XDG_DATA_HOME/app` or `/tmp/app`)

Note: Authorization headers are not required nor configured by default. If you need to add auth to your upstream openEHR/CKM server, extend the HTTP client in `src/Apis` to add the appropriate headers.

### Testing and QA

- Unit tests: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer test` (PHPUnit 12)
- Test with coverage: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer test:coverage`
- Static analysis: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer check:phpstan`

Tips
- You can also `make sh` and run `composer test` inside the container interactively.

### Project Structure

- `public/index.php`: MCP server entry point
- `resources/`: various resources used or exposed by the server
- `src/`
  - `Tools/`: MCP Tools (Definition, EHR, Composition, Query)
  - `Prompts/`: MCP Prompts (including `AbstractPrompt` for loading Markdown-based prompts)
  - `Resources/`: MCP Resources and Resource Templates
  - `CompletionProviders/`: MCP Completion Providers
  - `Helpers/`: Internal helpers (e.g., content type and ADL mapping)
  - `Apis/`: Internal API clients
  - `constants.php`: loads env and defaults
- `docker-compose.yml`: services (`mcp`, `caddy`) for production-like run (Caddy on 443)
- `docker-compose.dev.yml`: dev overrides for services, exposing port 8343 via Caddy
- `Dockerfile`: multi-stage PHP-FPM build (development, production)
- `Makefile`: handy shortcuts
- `tests/`: PHPUnit and PHPStan config and tests

----

## Contributing

We welcome contributions! Please read CONTRIBUTING.md for guidelines on setting up your environment, coding style, testing, and how to propose changes. Most routine tasks can be executed via the Makefile.

See CHANGELOG.md for notable changes and update it with every release.

### License

MIT License - see `LICENSE`.

----

## Acknowledgments

This project is inspired by and is grateful to:
- The original Python openEHR MCP Server: https://github.com/deak-ai/openehr-mcp-server
- [Seref Arikan](https://www.linkedin.com/in/seref-arikan/), [Sidharth Ramesh](https://www.linkedin.com/in/sidharthramesh1/) - for inspiration on MCP integration
- The PHP MCP Server framework: https://github.com/modelcontextprotocol/php-sdk
- [Ocean Health Systems](https://oceanhealthsystems.com/) for the Clinical Knowledge Manager (CKM), an essential tool for the openEHR community that enables collaborative development and sharing of archetypes and templates.
