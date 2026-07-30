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
| `inspector` | Dev-only, on-demand (`inspector` profile) — MCP Inspector v2 UI; see [below](#testing-with-the-mcp-inspector-ui). |

## Transports (REQ-F9)

- **`streamable-http`** — default; SSE endpoint on `:8343` in dev.
- **`stdio`** — for CLI/desktop clients: `php public/index.php --transport=stdio`
  (inside the container). Option parsing lives in `src/Helpers/CliOptions.php`.

## Testing with the MCP Inspector UI

With the dev stack running (`make up-dev`), launch the
[MCP Inspector](https://github.com/modelcontextprotocol/inspector) (**v2**):

```bash
make inspector        # builds if needed, then prints the UI URL
                      # e.g. http://localhost:6274?MCP_INSPECTOR_API_TOKEN=...
```

Open the printed URL — the token is required — and pick a server from the list.
Two targets are seeded from [`.docker/inspector-servers.json`](../.docker/inspector-servers.json),
so there is nothing to type:

| Server | URL |
|--------|-----|
| `openehr-assistant-dev` | `http://ingress:8343/mcp` |
| `openehr-assistant-dev-vhost` | `http://openehr-assistant-mcp.local:8343/mcp` |

Stop it with `make inspector-stop`.

### Why those hostnames

The Inspector's **backend** dials the MCP server from inside its own container, so
the target must be a compose-network name — not a host-side one. Both seeded names
are in the app's `MCP_ALLOWED_HOSTS`, so the streamable-http transport's
DNS-rebinding check (SDK ≥ 0.6) accepts them. `ingress` is the compose service and
is independent of `DOMAIN`; the vhost form additionally needs
`openehr-assistant-mcp.local` in your hosts file.

Host-side addresses **do not** work from the container and fail in distinct ways:
`host.docker.internal:8343` resolves but is rejected with
`403 Forbidden: Invalid Host header` (not in the allow-list), and `127.0.0.1:8343`
is the Inspector container's own loopback, so the connection is refused.

### CLI and TUI

v2 also ships a scriptable CLI and a terminal UI, useful for a fast loop with no
browser. Route them through the image's entrypoint wrapper (see the gotcha below);
`--config`/`--server` reuse the same seeded targets:

```bash
docker compose -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml \
  exec inspector /usr/local/bin/inspector-entrypoint --cli \
  --config /config/inspector-servers.json --server openehr-assistant-dev \
  --method tools/list
```

An ad-hoc URL works too: `… --cli http://ingress:8343/mcp --method tools/list`.

### Gotcha — v2 needs an OS keyring

v2 keeps per-server secrets in an OS keychain (`@napi-rs/keyring` → libsecret over
D-Bus). The upstream image ships neither, so **every** `/api/servers` call fails
with `Couldn't access platform storage: PermissionDenied` and the UI cannot list or
add any server. The `inspector` stage in [`.docker/Dockerfile`](../.docker/Dockerfile)
therefore installs `libsecret`/`gnome-keyring` and runs the Inspector inside a
private D-Bus session with a throwaway unlocked keyring — see
[`.docker/inspector-entrypoint.sh`](../.docker/inspector-entrypoint.sh).

Because `dbus-run-session` wraps only the container's main process, a plain
`docker compose exec … mcp-inspector` hits the same error — invoke
`/usr/local/bin/inspector-entrypoint` instead, as shown above.

### Migrating from v1

`:latest` moved to the v2.0.0 rewrite on 2026-07-28, so the image is pinned by
digest in the `inspector` stage; bump it deliberately. What changed:

| | v1 | v2 |
|---|---|---|
| Auth token env var | `MCP_PROXY_AUTH_TOKEN` | `MCP_INSPECTOR_API_TOKEN` |
| Printed URL | `…:6274/?TOKEN=…` | `…:6274?TOKEN=…` (no `/`) |
| Proxy port `6277` | separate proxy process | removed |
| "Via proxy" toggle | UI checkbox | removed — always server-side |
| Server list | typed into the UI | `--config` (read-only) / `--catalog` (writable) |
| Packaging | three sub-packages | one package: `--web` / `--cli` / `--tui` |

Drop the `--config` flag from the `inspector` service's `command` if you would
rather add servers ad-hoc in the UI; they then persist to the container's own
writable catalog (`~/.mcp-inspector/mcp.json`), which is not volume-mounted and so
is lost when the container is removed.

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

Capability discovery is cached (Symfony Cache `PhpFilesAdapter`, under
`XDG_DATA_HOME`) for fast startup — see
[ADR-0001](decisions/0001-attribute-driven-discovery.md). The cache pool is
namespaced by `APP_VERSION` (`src/constants.php`), so a version bump
automatically invalidates stale entries whenever the discovery schema, an
attribute signature, or the tool/prompt/resource set changes as part of a
release — no manual step needed in that case.

**During local development**, when adding or renaming a tool/prompt/resource/
completion-provider class *without* bumping `APP_VERSION`, clear the cache
directory under `XDG_DATA_HOME` (default `/tmp`) yourself, or the new
capability will not register. For the same reason, **deploys that don't bump
`APP_VERSION`** (e.g. hotfixes to capability code) should also clear the cache
directory as part of the deploy step. Containerised deploys get this for free —
`APP_DATA_DIR` defaults to the ephemeral `/tmp/app`.

Two consequences of the namespacing worth knowing:

- The pool name becomes a **subdirectory** (`$XDG_DATA_HOME/app/cache/mcp-server-<version>/`)
  and entries have no TTL, so every release leaves its predecessor's cache behind.
  Prune `$XDG_DATA_HOME/app/cache` periodically on long-lived hosts.
- `APP_VERSION` is now part of a filesystem path, so it must stay within
  `[-+_.A-Za-z0-9]`; any other character makes Symfony Cache throw at startup.

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
