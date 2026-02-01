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

## Features

- Works with MCP clients such as Claude Desktop, Cursor, LibreChat or other clients that support MCP
- Exposes tools for openEHR Archetypes and specifications
- Optional guided Prompts help orchestrate multi-step workflows
- Run remotely (endpoint URL: https://openehr-assistant-mcp.apps.cadasto.com/) or locally (transports: streamable HTTP and stdio)

### Implementation aspects 

- Made with PHP 8.4; PSR-compliant codebase
- Attribute-based MCP tool discovery (via https://github.com/mcp/sdk)
- Attribute-based MCP prompt discovery (seeded conversations for complex tasks)
- MCP Resource templates and Completion Providers for better UX in MCP clients
- Transports: streamable HTTP and stdio (for development)
- Docker images for production and development
- Structured logging with Monolog

## Available MCP Elements

### Tools

CKM (Clinical Knowledge Manager)
- `ckm_archetype_search` - List Archetypes from the CKM server matching search criteria
- `ckm_archetype_get` - Get a CKM Archetype by its identifier
- `ckm_template_search` - List Templates (OET/OPT) from the CKM server matching search criteria
- `ckm_template_get` - Get a CKM Template (OET/OPT) by its identifier

openEHR Terminology
- `terminology_resolve` - Resolve an openEHR terminology concept ID to its rubric, or find the ID for a given rubric across groups.

openEHR Type specification
- `type_specification_search` - List bundled openEHR Type specifications matching search criteria.
- `type_specification_get` - Retrieve an openEHR Type specification (as BMM JSON).

### Prompts

Optional prompts that guide AI assistants through common openEHR and CKM workflows using the tools above.
- `ckm_archetype_explorer` - Explore CKM Archetypes by discovering and fetching definitions (ADL/XML/Mindmap), using `ckm_archetype_search` and `ckm_archetype_get` tools.
- `ckm_template_explorer` - Explore CKM Templates by discovering and fetching definitions (OET/OPT), using `ckm_template_search` and `ckm_template_get` tools.
- `type_specification_explorer` - Discover and fetch openEHR Type specifications (as BMM JSON) using `type_specification_search` and `type_specification_get` tools.
- `terminology_explorer` - Discover and retrieve openEHR terminology definitions (groups and codesets) using terminology resources.
- `explain_archetype` - Explain an archetype’s semantics (audiences, elements, constraints).
- `explain_template` - Explain openEHR Template semantics.
- `translate_archetype_language` - Translate an archetype’s terminology section between languages with safety checks.
- `fix_adl_syntax` - Correct or improve Archetype syntax without changing semantics; provides before/after and notes.
- `design_or_review_archetype` - Design or review task for a specific concept/RM class with structured outputs.
- `design_or_review_template` - Design or review task for an openEHR Template (OET).

### Completion Providers

Completion providers supply parameter suggestions in MCP clients when invoking tools or resources.
- `Guides` - suggests guide `{name}` values from `resources/guides/archetypes` resource URI
- `SpecificationComponents` - suggests `{component}` values based on directories in `resources/bmm`  resource URI

### Resources

MCP Server Resources are exposed via `#[McpResource]` annotated methods and can be fetched by MCP clients using `openehr://...` URIs. They are used most of the time in the prompts above, to inject data or instructions into the conversation.

Guides (Markdown)
- URI template: `openehr://guides/{category}/{name}`
- On-disk mapping: `resources/guides/{category}/{name}.md`
- Examples:
  - `openehr://guides/archetypes/checklist`
  - `openehr://guides/archetypes/adl-syntax`

Type Specifications (BMM JSON)
- URI template: `openehr://spec/type/{component}/{name}`
- On-disk mapping: `resources/bmm/{COMPONENT}/{NAME}.bmm.json`
- Examples:
  - `openehr://spec/type/RM/COMPOSITION`
  - `openehr://spec/type/AM/ARCHETYPE`
  - `openehr://spec/type/AM2/ARCHETYPE_HRID`

Terminologies (JSON)
- URI template: `openehr://terminology/{type}/{id}`
- URI: `openehr://terminology/all` contains all terminology groups and codesets
- Examples:
  - `openehr://terminology/group/attestation_reason`
  - `openehr://terminology/codeset/compression_algorithms`
- Provides access to both terminology groups (concepts/rubrics) and codesets.
- On-disk mapping: `resources/terminology/openehr_terminology.xml`

## Transports

MCP Transports are used to communicate with MCP clients. 

- `streamable-http` (default): HTTPS (port 443); dev setup exposes an additional HTTP port `8343` via Caddy.
- `stdio`: Suitable for process-based MCP clients, or for local development. 
  - Start option: pass `--transport=stdio` to `public/index.php`.

---

## Quick Start

### Run remotely

The quickest way to use the assistant without local setup is to connect your MCP client to our hosted instance. 
This server is optimized for production use and is regularly updated with the latest version.
- Endpoint URL: https://openehr-assistant-mcp.apps.cadasto.com/
- Transport Type: streamable-http (SSE)

To connect this server to an MCP client like Claude Desktop, go to Settings → Connectors → Add custom Connector, or alternatively add the following to your mcpServers configuration file:
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

### Run locally

To run the MCP server locally, the easiest way is to use Docker. Depending on the use case, this can be either a production or development setup.

Prerequisites:
- Docker and Docker Compose
- Git

>NOTE: A pre-built Docker image is available on the GitHub Container Registry (GHCR) at `ghcr.io/cadasto/openehr-assistant-mcp:latest`. 
> You can use this image as a drop-in replacement for cadasto/openehr-assistant-mcp:latest in any of the stdio transport examples below.

1) Clone

```bash
git clone https://github.com/cadasto/openehr-assistant-mcp.git
cd openehr-assistant-mcp
```

2) Run the MCP server (production)

```bash
# optionally create .env file, edit it as needed
cp .env.example .env
# build the images locally and start the server
docker compose up -d --build
# or,
make up
```

The server listens by default at https://openehr-assistant-mcp.local (on port `443`) using the streamable HTTP transport, served by a separate [Caddy webserver](https://caddyserver.com/) container.

The domain suffix is `local` by default, but can be changed in the `.env` file; set the `DOMAIN` variable to the desired domain suffix.

### Development

For local development, changing provided tools, prompts, etc., the easiest way is to use the dev container setup, provided by `docker-compose.dev.yml` (overrides). This will volume mount the codebase inside the PHP container, and Caddy will expose port `8343`.

1) Start dev containers

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build --force-recreate
# or,
make up-dev
```

2) Install dependencies (composer)

Assuming you are running the dev container, and your local user ID is `1000`,
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec -u 1000:1000 mcp composer install
# or,
make install
```

3) Use the MCP server
Use the streamable HTTP transport with http://openehr-assistant-mcp.local:8343/ address in your MCP client.

### Run the MCP server with stdio transport

If you want to run the MCP server locally for development purposes, sometimes it is easier to use the stdio transport.

Command to run the MCP server as stdio:
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp php public/index.php --transport=stdio
```

If the MCP client does not have direct access to the docker compose project, first build the image with `make build`, then use `docker run` with the stdio transport:
```bash
docker run --rm -i cadasto/openehr-assistant-mcp:latest php public/index.php --transport=stdio
```

or alternatively, use the latest github container registry image directly (no need to build the image):
```bash
docker run --rm -i ghcr.io/cadasto/openehr-assistant-mcp:latest php public/index.php --transport=stdio
```

### MCP inspector

The MCP inspector is a handy tool to inspect the MCP server responses and debug issues.
It provides a simple web interface to inspect server responses and send requests to the server.

>NOTE: This tools works best if the MCP server is up and running as the dev container.

To run the inspector, use `make inspector` and follow the instructions in the terminal. 

>NOTE: The url of the inspector is printed in the terminal as http://0.0.0.0:6274/, but in reality it should be accessed with an exact IP or host name, for example, at http://localhost:6274/.


### Makefile shortcuts:
 
- Build images: `make build` (prod) or `make build-dev` (dev)
- Start services: `make up` (prod) or `make up-dev` (dev override with live volume mounts)
- Prepare `.env` (make a copy from example): `make env`
- Install deps in dev container: `make install`
- Tail logs: `make logs`
- Open a shell in the dev container: `make sh`
- Run MCP inspector: `make inspector`
- Make help: `make help`

## Environment Variables

- `APP_ENV`: application environment (`development`/`testing`/`production`). Default: `production`
- `LOG_LEVEL`: Monolog level (`debug`, `info`, `warning`, `error`, etc.). Default: `info`
- `CKM_API_BASE_URL`: base URL for the openEHR CKM REST API. Default: `https://ckm.openehr.org/ckm/rest`
- `HTTP_TIMEOUT`: HTTP client timeout in seconds (float). Default: `3.0`
- `HTTP_SSL_VERIFY`: set to `false` to disable verification or provide a CA bundle path. Default: `true`

Note: Authorization headers are not required nor configured by default. If you need to add auth to your upstream openEHR/CKM server, extend the HTTP client in `src/Apis` to add the appropriate headers.

## MCP Client Integrations 

### Claude Desktop mcpServers example

Example for local development (use Docker)
```json
{
  "mcpServers": {
    "openehr-assistant-mcp": {
      "command": "docker",
      "args": [
        "run", "-i", "--rm", 
        "ghcr.io/cadasto/openehr-assistant-mcp:latest",
        "php", "public/index.php", "--transport=stdio"
      ]
    }, 
    "openehr-assistant-mcp-8343": {
      "type": "streamable-http",
      "url": "http://host.docker.internal:8343/",
      "note": "Assumes the dev container is running and port 8343 mapped to host"
    }
  }
}
```

### Streamable HTTP in LibreChat

LibreChat.ai MCP example
- Run first the MCP server (see above, e.g. `docker compose up -d` or `make up`)
- The dev server is accessible at http://localhost:8343/
- Run the LibreChat server (see https://github.com/LibreChat/librechat-server)
- Configure LibreChat to use the MCP server (see https://github.com/LibreChat/librechat-server/blob/main/docs/mcp.md)
- The server is compatible with LibreChat’s MCP integration. Example minimal server entry in LibreChat config (YAML):
```yaml
mcpServers:
    openehr-assistant-mcp:
        type: streamable-http
        url: http://host.docker.internal:8343/
```

## Testing and QA

- Unit tests: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer test` (PHPUnit 12)
- Test with coverage: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer test:coverage`
- Static analysis: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer check:phpstan`

Tips
- You can also `make sh` and run `composer test` inside the container interactively.

## Project Structure

- `public/index.php`: MCP server entry point
- `resources/`: various resources used or exposed by the server
- `src/`
  - `Tools/`: MCP Tools (Definition, EHR, Composition, Query)
  - `Prompts/`: MCP Prompts (including `AbstractPrompt` for loading YAML-based prompts)
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

## Acknowledgments

This project is inspired by and is grateful to:
- The original Python openEHR MCP Server: https://github.com/deak-ai/openehr-mcp-server
- [Seref Arikan](https://www.linkedin.com/in/seref-arikan/), [Sidharth Ramesh](https://www.linkedin.com/in/sidharthramesh1/) - for inspiration on MCP integration
- The PHP MCP Server frameworks: https://github.com/php-mcp/server and https://github.com/modelcontextprotocol/php-sdk
- [Ocean Health Systems](https://oceanhealthsystems.com/) for the Clinical Knowledge Manager (CKM), an essential tool for the openEHR community that enables collaborative development and sharing of archetypes and templates.

## Contributing

We welcome contributions! Please read CONTRIBUTING.md for guidelines on setting up your environment, coding style, testing, and how to propose changes. Most routine tasks can be executed via the Makefile.

See CHANGELOG.md for notable changes and update it with every release.

## License

MIT License - see `LICENSE`.
