# The openEHR Assistant MCP Server (PHP)

A PHP 8.4 [Model Context Protocol (MCP) Server](https://modelcontextprotocol.io/docs/getting-started/intro) to assist end-user on various openEHR related tasks and APIs.

- Works with MCP clients such as Claude Desktop, Cursor, LibreChat or other clients that support MCP
- Exposes tools for openEHR Archetypes and specifications
- Optional guided Prompts help orchestrate multi-step workflows

## Features

- PHP 8.4; PSR-compliant codebase
- Attribute-based MCP tool discovery (via https://github.com/mcp/sdk)
- Attribute-based MCP prompt discovery (seeded conversations for complex tasks)
- Docker images for production and development
- Transports: streamable HTTP and stdio (for development)
- Structured logging with Monolog
- Simple, environment-driven configuration
- Built-in developer guidelines exposed as MCP Resources via `openehr://guides/{category}/{name}` URIs
- MCP Resource templates and Completion Providers for better UX in MCP clients

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
- `type_specification_get` - Retrieve an openEHR Type specification (as BMM JSON) by relative file path or by openEHR Type name.

### Prompts

Optional prompts that guide AI assistants through common openEHR and CKM workflows using the tools above.
- `ckm_archetype_explorer` - Explore CKM Archetypes by discovering and fetching definitions (ADL/XML/Mindmap), using `ckm_archetype_search` and `ckm_archetype_get` tools.
- `ckm_template_explorer` - Explore CKM Templates by discovering and fetching definitions (OET/OPT), using `ckm_template_search` and `ckm_template_get` tools.
- `type_specification_explorer` - Discover and fetch openEHR Type specifications (as BMM JSON) using `type_specification_search` and `type_specification_get` tools.
- `terminology_explorer` - Discover and retrieve openEHR terminology definitions (groups and codesets) using terminology resources.
- `explain_archetype_semantics` - Explain an archetype’s semantics (audiences, elements, constraints).
- `translate_archetype_language` - Translate an archetype’s terminology section between languages with safety checks.
- `fix_adl_syntax` - Correct or improve Archetype syntax without changing semantics; provides before/after and notes.
- `design_or_review_archetype` - Guide a design or review task for a specific concept/RM class with structured outputs.

### Completion Providers

Completion providers supply parameter suggestions in MCP clients when invoking tools or resources.
- `Guides` - suggests guide `{name}` values from `resources/guides/archetypes` resource URI
- `SpecificationComponents` - suggests `{component}` values based on directories in `resources/bmm`  resource URI

### Resources

Resources are exposed via `#[McpResourceTemplate]` annotated methods and can be fetched by MCP clients using `openehr://...` URIs.

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

Terminologies (JSON)
- URI template: `openehr://terminology/{type}/{id}`
- On-disk mapping: `resources/terminology/openehr_terminology.xml`
- Examples:
  - `openehr://terminology/group/attestation_reason`
  - `openehr://terminology/codeset/compression_algorithms`
- Provides access to both terminology groups (concepts/rubrics) and codesets.

## Transports

- `stdio`: Suitable for process-based MCP clients
- `streamable-http` (default): HTTPS (port 443) or HTTP server on port `8343` on the dev container.

Start option: pass `--transport=stdio` or `--transport=streamable-http` to `public/index.php`; if `--transport` is skipped, the default is `streamable-http`.

## Quick Start with Docker

Prerequisites
- Docker and Docker Compose
- Git

>NOTE: A Docker image is regurly published to GitHub Container Registry (GHCR) as `ghcr.io/cadasto/openehr-assistant-mcp:latest`. 
> Use this image name anywhere you see `cadasto/openehr-assistant-mcp:latest` in the examples below.

1) Clone

```bash
git clone https://github.com/cadasto/openehr-assistant-mcp.git
cd openehr-assistant-mcp
```

2) Run the MCP server (production image)

```bash
# optionally create .env file
cp .env.example .env
# Edit .env as needed (see variables below)
docker compose up -d mcp --build
```

The server listens by default at https://openehr-assistant-mcp.local (on port `443`) using the streamable HTTP transport, served by Caddy inside the container.
You can change the domain suffix by setting the `DOMAIN` variable (defaults to `local`).

## Development

Prerequisites
- Docker and Docker Compose

1) Start dev container

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d mcp --build --force-recreate
```

2) Configure environment

```bash
cp .env.example .env
# Edit .env as needed (see variables below)
```

3) Install dependencies (composer vendor directory is volume mounted)

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer install
```

4) Use the MCP server
Use the streamable HTTP transport with http://openehr-assistant-mcp.local:8343/ address in your MCP client.
> Note: The dev container is configured to expose port 8343 on the host.
> The codebase is volume-mounted inside the container. 

Alternatively, run the MCP server as stdio:
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp php public/index.php --transport=stdio
```
or, in case the MCP client does not have direct access to the docker compose project, but the image is already built (use `make build` to build the image):
```bash
docker run --rm -i cadasto/openehr-assistant-mcp:latest php public/index.php --transport=stdio
```

or alternatively, in case the MCP client does not have direct access to the docker compose project, but the image is already built (use `make build` to build the image):
```bash
docker run --rm -i cadasto/openehr-assistant-mcp:latest php public/index.php --transport=stdio
```

Makefile shortcuts:
- Start services: `make up` (prod) or `make up-dev` (dev override with live mounts)
- Build images: `make build` (prod) or `make build-dev` (dev)
- Prepare `.env`: `make env`
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

## Integrations (Claude Desktop and LibreChat)

### Claude Desktop mcpServers example

Example for local development (use Docker)
```json
{
  "mcpServers": {
    "openehr-assistant-mcp": {
      "command": "docker",
      "args": [
        "run", "-i", "--rm", 
        "cadasto/openehr-assistant-mcp:latest",
        "php", "public/index.php", "--transport=stdio"
      ]
    }, 
    "openehr-assistant-mcp-8343": {
      "type": "streamable-http",
      "url": "http://host.docker.internal:8343/",
      "note": "Dev running container port 8343"
    }
  }
}
```

### Streamable HTTP in LibreChat

LibreChat.ai MCP example
- Run first the MCP server (see above, e.g. `docker compose up -d mcp` or `make up`)
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
- `src/`
  - `Tools/`: MCP Tools (Definition, EHR, Composition, Query)
  - `Prompts/`: MCP Prompts
  - `Resources/`: MCP Resources and Resource Templates
  - `CompletionProviders/`: MCP Completion Providers
  - `Helpers/`: Internal helpers (e.g., content type and ADL mapping)
  - `Apis/`: Internal API clients
  - `constants.php`: loads env and defaults
- `docker-compose.yml`: services (`mcp`) for production-like run (Caddy on 443)
- `docker-compose.dev.yml`: dev overrides for service (`mcp`) exposing port 8343 and mounting source
- `Dockerfile`: multi-stage build (development, production)
- `Makefile`: handy shortcuts
- `resources/`: various resources used or exposed by the server
- `tests/`: PHPUnit and PHPStan config and tests

## Acknowledgments

This project is inspired by and is grateful to:
- The original Python openEHR MCP Server: https://github.com/deak-ai/openehr-mcp-server
- Seref Arikan, Sidharth Ramesh - for inspiration on MCP integration
- The PHP MCP Server frameworks: https://github.com/php-mcp/server and https://github.com/modelcontextprotocol/php-sdk

## Contributing

We welcome contributions! Please read CONTRIBUTING.md for guidelines on setting up your environment, coding style, testing, and how to propose changes. Most routine tasks can be executed via the Makefile.

See CHANGELOG.md for notable changes and update it with every release.

## License

MIT License - see `LICENSE`.
