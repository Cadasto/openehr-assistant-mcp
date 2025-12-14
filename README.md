# The openEHR Assistant MCP Server (PHP)

A PHP 8.4 [Model Context Protocol (MCP) Server](https://modelcontextprotocol.io/docs/getting-started/intro) to assist end-user on various openEHR related tasks and APIs.

- Works with MCP clients such as Claude Desktop, Cursor, LibreChat or other clients that support MCP
- Exposes tools for openEHR Archetypes and specifications
- Optional guided Prompts help orchestrate multi-step workflows

## Features

- PHP 8.4; PSR-compliant codebase
- Attribute-based MCP tool discovery (via https://github.com/mcp/sdk)
- Docker images for production and development
- Transports: streamable HTTP and stdio (for development)
- Structured logging with Monolog
- Simple, environment-driven configuration

## Available MCP Elements

### Tools

CKM (Clinical Knowledge Manager)
- `ckm_archetype_search` - List archetypes from the CKM server
- `ckm_archetype_get` - Get a CKM archetype by its CID identifier

openEHR Type specification
- `type_specification_search` - List bundled openEHR Type specifications using `namePattern` (supports `*` wildcard) and an optional keyword (filters by type specification content). Returns `type`, `description`, `component`, and `file` (relative path).
- `type_specification_get` - Retrieve an openEHR Type specification (as BMM JSON) by relative file path or by openEHR type name. Note: these are BMM type definitions, not JSON Schema

### Prompts

Optional prompts that guide AI assistants through common openEHR and CKM workflows using the tools above.
- `ckm_archetype_explorer` - Explore CKM archetypes by listing and fetching definitions (ADL/XML/Mindmap) by CID.
- `type_specification_explorer` - Discover and fetch openEHR Type specifications (as BMM JSON) using type_specification_search and type_specification_get.

## Transports

- `stdio`: Suitable for process-based MCP clients
- `streamable-http` (default): HTTPS (port 443) or HTTP server on port `8343` on the dev container.

Start option: pass `--transport=stdio` or `--transport=streamable-http` to `public/index.php`; if `--transport` is skipped, the default is `streamable-http`.

## Quick Start with Docker

Prerequisites
- Docker and Docker Compose
- Git

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

The server listens by default at https://openehr-assistant-mcp.local on port `443` using the streamable HTTP transport, served by Caddy inside the container.
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
> The codebase is mounted inside the container.
 

Alternatively, run the MCP server as stdio:
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp php public/index.php --transport=stdio
```
or, in case the MCP client does not have direct access to the docker compose project, but the image is already built (use `make build` to build the image):
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
- Run MCP inspector: `make inspector
- Make help: `make help`

## Environment Variables

- `APP_ENV`: application environment (`development`/`production`). Default: `development`
- `LOG_LEVEL`: Monolog level (`debug`, `info`, `warning`, `error`, etc.). Default: `info`
- `CKM_API_BASE_URL`: base URL for the openEHR CKM REST API. Default: `https://ckm.openehr.org/ckm/rest`
- `HTTP_TIMEOUT`: HTTP client timeout in seconds (float). Default: `2.0`
- `HTTP_SSL_VERIFY`: set to `false` to disable verification or provide a CA bundle path. Default: `true`

Note: Authorization headers are not required nor configured by default. If you need to add auth to your upstream openEHR/CKM server, extend the HTTP client in `src/Apis` to add the appropriate headers.

## Integrations (Claude Desktop and LibreChat)

### Claude Desktop mcpServers example

Stdio example for local development (use Docker)
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
  - `Helpers/`: Internal helpers (e.g., content type and ADL mapping)
  - `Apis/`: Internal API clients
  - `constants.php`: loads env and defaults
- `docker-compose.yml`: services (`mcp`) for production-like run (Caddy on 443)
- `docker-compose.dev.yml`: dev overrides for service (`mcp`) exposing port 8343 and mounting source
- `Dockerfile`: multi-stage build (development, production)
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
