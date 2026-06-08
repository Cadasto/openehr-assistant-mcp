# Installation & Client Setup

How to connect an MCP client to the openEHR Assistant MCP Server — either the
hosted endpoint (no install) or a local instance. For contributing to the
server itself, see [development.md](development.md).

> **Tip:** For the best experience, also install the user-facing
> [openEHR Assistant Plugin](https://github.com/cadasto/openehr-assistant-plugin)
> (skills, prompts, and agents that guide AI assistants through openEHR workflows).

## Option 1 — Hosted endpoint (no install)

The fastest path: point your client at the hosted server.

| | |
|---|---|
| **URL** | `https://openehr-assistant-mcp.apps.cadasto.com/` |
| **Transport** | `streamable-http` |

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

## Option 2 — Run locally with Docker

Use this to run your own instance (also the basis for contributing).

**Prerequisites:** Docker + Docker Compose, Git.

```bash
git clone https://github.com/cadasto/openehr-assistant-mcp.git
cd openehr-assistant-mcp
cp .env.example .env          # defaults work for most users
make up-dev                   # start dev containers
make install                  # install Composer dependencies (in the container)
```

Local endpoints once the stack is up:

| Endpoint | URL |
|----------|-----|
| Dev HTTP (host) | `http://localhost:8343/` |
| Dev HTTP (from another container, e.g. LibreChat) | `http://host.docker.internal:8343/` |
| Named host (optional) | `http://openehr-assistant-mcp.local:8343/` |

> To use `openehr-assistant-mcp.local`, add `127.0.0.1 openehr-assistant-mcp.local`
> to your hosts file. If it doesn't resolve, just use `http://localhost:8343/`.
> (`make` and Docker commands are documented in [development.md](development.md).)

## Option 3 — Run locally via stdio

For clients that launch the server process directly.

```bash
# From the dev container:
docker compose --env-file .env -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml \
  exec app php public/index.php --transport=stdio

# Or from the published image:
docker run --rm -i ghcr.io/cadasto/openehr-assistant-mcp:latest php public/index.php --transport=stdio
```

## Client configurations

Add **one** server entry to your client. Pick the transport that matches your setup
(hosted / local HTTP / stdio).

```json
{
  "mcpServers": {
    "openehr-assistant-mcp": {
      "type": "streamable-http",
      "url": "https://openehr-assistant-mcp.apps.cadasto.com/"
    },
    "openehr-assistant-mcp-http": {
      "type": "streamable-http",
      "url": "http://host.docker.internal:8343/"
    },
    "openehr-assistant-mcp-stdio": {
      "command": "docker",
      "args": [
        "run", "-i", "--rm",
        "ghcr.io/cadasto/openehr-assistant-mcp:latest",
        "php", "public/index.php", "--transport=stdio"
      ]
    }
  }
}
```

### Claude Desktop
Add the hosted URL via **Menu → Settings → Connectors → Add custom connector**,
or use **Menu → Developer → Edit Config** and add one of the entries above.

### LibreChat (streamable HTTP)
```yaml
mcpServers:
  openehr-assistant-mcp:
    type: streamable-http
    url: http://host.docker.internal:8343/
```

### Cursor
**Cursor Settings → MCP → Add server**, then choose:
- **Hosted:** `type=streamable-http`, `url=https://openehr-assistant-mcp.apps.cadasto.com/`
- **Local dev:** `type=streamable-http`, `url=http://host.docker.internal:8343/`
- **Local stdio:** the Docker command above.

### IntelliJ Junie
**Settings → Tools → Junie → MCP Servers** (wording varies by version). Add a
**Streamable HTTP** URL (hosted or `http://host.docker.internal:8343/`) or the
**stdio** Docker command, then refresh/restart Junie so tools are discovered.
