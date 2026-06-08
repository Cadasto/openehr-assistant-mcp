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

## Maintainer tooling (Claude Code / Cursor)

When working **on** this repo with Claude Code or Cursor, install and activate the
[openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin)
— maintainer tooling that supplies authoring skills (guides, prompts, MCP tools,
examples) and the release workflow, plus a `SessionStart` hook with the dev commands.
It is **not** the user-facing [openehr-assistant](https://github.com/cadasto/openehr-assistant-plugin)
plugin (that wraps the server for clinical end users — don't confuse the two).

Claude Code (the repo already enables it in `.claude/settings.json`; you just add the marketplace and install):

```
/plugin marketplace add cadasto/plugin-marketplace
/plugin install openehr-assistant-dev@cadasto
```

Cursor: add the dev-plugin repository under **Settings → Plugins** (Git URL or local path).

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
| `APP_ENV` | `development` / `testing` / `production` | `production` |
| `LOG_LEVEL` | Monolog level (`debug`, `info`, `warning`, `error`, …) | `info` |
| `CKM_API_BASE_URL` | CKM REST API base URL | `https://ckm.openehr.org/ckm/rest` |
| `HTTP_TIMEOUT` | Guzzle client timeout, seconds (float) | `3.0` (`.env`) |
| `HTTP_SSL_VERIFY` | `false` to disable, or a CA bundle path | `true` |
| `XDG_DATA_HOME` | App data dir (cache + sessions, incl. MCP discovery cache) | `/tmp` |
| `MCP_ALLOWED_HOSTS` | `streamable-http` DNS-rebinding allow-list (SDK ≥ 0.6); comma-separated hostnames. Set to the reverse-proxy host / public domain in deployments. | `localhost,127.0.0.1,[::1]` |

> **Auth:** no authorization headers are required or configured by default. To add
> auth to an upstream openEHR/CKM server, extend the HTTP client in `src/Apis/`.

## Gotcha — MCP discovery cache

Capability discovery is cached (Symfony Cache, under `XDG_DATA_HOME`) for fast
startup — see [ADR-0001](decisions/0001-attribute-driven-discovery.md). **After
adding or renaming a tool/prompt/resource/completion-provider class, clear the
cache** (the cache directory under `XDG_DATA_HOME`, default `/tmp`) or the new
capability will not register.

## Troubleshooting

- **Port `8343` already in use** — change the published `ingress` port in `.docker/docker-compose.dev.yml`.
- **Coverage needs Xdebug** — use `composer test:coverage`, which sets `XDEBUG_MODE`.
- **SSL errors in dev** — set `HTTP_SSL_VERIFY=false` (never in production).
- **WSL2 (Windows)** — keep the checkout inside the WSL filesystem for performance.
- **New capability not appearing** — clear the discovery cache (see the gotcha above).

## Optional local (non-Docker) workflow

If you already have PHP 8.4 + required extensions locally, you *can* run tools
directly — but this is unsupported and not the documented default:

```bash
composer install
composer test
composer check:phpstan
```

## Makefile shortcuts

`make help` lists everything. Common targets:

| Target | Purpose |
|--------|---------|
| `make up-dev` / `make up` | Start dev (live mounts, port 8343) / production-like containers |
| `make install` | Install Composer deps in the dev container |
| `make ci` | PHPStan + tests (mirrors PR validation) |
| `make conformance` | MCP conformance suite (stack must be up) |
| `make inspector` / `make inspector-stop` | Start / stop the MCP Inspector UI |
| `make run-stdio` | Run the server over stdio |
| `make sh` | Shell into the dev container |
| `make logs` / `make ps` | Tail logs / list containers |
| `make down` / `make clean` | Stop / stop and remove volumes |
| `make build` / `make build-dev` | Build prod / dev images |
| `make env` | Create `.env` from `.env.example` |

## Project structure

- `public/index.php` — MCP server entry point (registers capabilities, selects transport)
- `src/`
  - `Tools/` — MCP tools (`#[McpTool]`)
  - `Prompts/` — MCP prompts (`#[McpPrompt]`; `AbstractPrompt` loads Markdown bodies)
  - `Resources/` — MCP resources and resource templates
  - `CompletionProviders/` — argument completion providers
  - `Helpers/` — internal helpers (e.g. content-type / ADL mapping, CLI options)
  - `Apis/` — internal API clients (CKM)
  - `constants.php` — loads env and defaults (incl. `APP_VERSION`)
- `resources/` — guides, examples, BMM JSON, terminology, prompt bodies, `server-instructions.md`
- `.docker/` — `docker-compose.yml` (`app`, `ingress`), `docker-compose.dev.yml` (port 8343, `node`), multi-stage `Dockerfile`, `Caddyfile`, PHP config
- `tests/` — PHPUnit tests + PHPStan config
- `docs/` — Specification-Driven Development docs (see [README](README.md))

For the component-level architecture (layers, the single CKM HTTP boundary, the
test mirror), see [architecture.md](architecture.md).

## Next

- Running and validating the suite → [testing.md](testing.md)
- What the components are → [architecture.md](architecture.md)
