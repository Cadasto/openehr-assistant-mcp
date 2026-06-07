# Development Environment

> Operational companion to the [SDD docs](README.md). Satisfies REQ-N5 (Docker-only
> runtime) — see [ADR-0004](decisions/0004-docker-only-runtime.md). Conventions
> and project structure are canonical in [AGENTS.md](../AGENTS.md).

## Docker-only runtime (critical)

There is **no local PHP or Composer** on the host (WSL2 on Windows). Every `php`,
`composer`, and `vendor/bin/*` command **must** run inside the dev container
(`app` service) — running them on the host will fail. The container expects host
UID `1000`; adjust the `-u` flag below if yours differs.

## Quick start

```bash
make up-dev      # start dev containers (.docker/docker-compose.dev.yml overrides)
make install     # install Composer dev dependencies (inside the container)
```

The dev override mounts the source and exposes the `streamable-http` SSE endpoint
on port **8343** (via the Caddy `ingress` reverse proxy). PHP 8.4 is provided by
the multi-stage `.docker/Dockerfile`.

## Services (`.docker/docker-compose*.yml`)

| Service | Role |
|---------|------|
| `app` | Application container — production-like and (with dev overrides) development runs. |
| `ingress` | Caddy reverse proxy. |
| `node` | Dev-only — Node + curl, used by `make conformance` and other npx/curl tooling. |

## Transports (REQ-F9)

- **`streamable-http`** — default; SSE endpoint on `:8343` in dev.
- **`stdio`** — for CLI/desktop clients: `php public/index.php --transport=stdio`
  (inside the container). Option parsing lives in `src/Helpers/CliOptions.php`.

## Testing with the MCP Inspector UI

With the dev stack running (`make up-dev`), launch the [MCP Inspector](https://github.com/modelcontextprotocol/inspector):

```bash
make inspector        # prints the UI URL, e.g. http://localhost:6274/?MCP_PROXY_AUTH_TOKEN=...
```

Open the printed URL (the token is required), then configure the connection:

| Field | Value |
|-------|-------|
| **Transport Type** | `Streamable HTTP` |
| **URL** | `http://openehr-assistant-mcp.local:8343/` |
| **Via proxy** | ✅ checked |

Notes:

- **Via proxy** must be checked — the connection is made by the Inspector's proxy
  process (on the dev network), not directly from your browser.
- `openehr-assistant-mcp.local` must resolve to the dev host on your machine — add a
  hosts-file entry if needed. It is pre-listed in `MCP_ALLOWED_HOSTS`, so the
  streamable-http transport's DNS-rebinding check (SDK ≥ 0.6) accepts that host.

Stop it with `make inspector-stop`.

## Configuration

Environment variables are read from `.env` (template in `.env.example`):

| Variable | Purpose | Default |
|----------|---------|---------|
| `CKM_API_BASE_URL` | CKM REST API base URL | `https://ckm.openehr.org/ckm/rest` |
| `LOG_LEVEL` | Monolog level (e.g. `debug`) | — |
| `HTTP_TIMEOUT`, `HTTP_SSL_VERIFY` | Guzzle client settings (`CkmClient`) | — |
| `XDG_DATA_HOME` | App data dir (cache + sessions, incl. MCP discovery cache) | `/tmp` |
| `MCP_ALLOWED_HOSTS` | `streamable-http` DNS-rebinding allow-list (SDK ≥ 0.6); comma-separated hostnames. Set to the reverse-proxy host / public domain in deployments. | `localhost,127.0.0.1,[::1]` |

## Gotcha — MCP discovery cache

Capability discovery is cached (Symfony Cache, under `XDG_DATA_HOME`) for fast
startup — see [ADR-0001](decisions/0001-attribute-driven-discovery.md). **After
adding or renaming a tool/prompt/resource/completion-provider class, clear the
cache** (the cache directory under `XDG_DATA_HOME`, default `/tmp`) or the new
capability will not register.

## Optional local (non-Docker) workflow

If you already have PHP 8.4 + required extensions locally, you *can* run tools
directly — but this is unsupported and not the documented default:

```bash
composer install
composer test
composer check:phpstan
```

## Next

- Running and validating the suite → [testing.md](testing.md)
- What the components are → [architecture.md](architecture.md)
