# AI Guidelines: openEHR Assistant MCP Server

These guidelines summarize the high-level architecture, coding conventions, and developer workflows for this repository, with the goal of helping an AI agent quickly discover available tooling and work within the project structure.

## Documentation map

This repo follows a lightweight **Specification-Driven Development** paradigm. The
spec set under [`docs/`](docs/README.md) is the source of truth; AGENTS.md is the
concise conventions layer that links into it.

- [`docs/requirements.md`](docs/requirements.md) — `REQ-#` functional + non-functional requirements (the *what*).
- [`docs/architecture.md`](docs/architecture.md) — components mapped to requirements (the *how*).
- [`docs/decisions/`](docs/decisions/README.md) — Architecture Decision Records (the *why*).
- [`docs/traceability.md`](docs/traceability.md) — REQ ↔ code ↔ test ↔ ADR matrix.
- [`docs/development.md`](docs/development.md) · [`docs/testing.md`](docs/testing.md) — Docker dev environment and the test/validation workflow.
- [`docs/install.md`](docs/install.md) — hosted & local setup and MCP client configurations (user-facing).

> **Maintainer tooling.** Authoring tools/prompts/guides/examples and managing
> releases for this repo is supported by the
> [openehr-assistant-dev plugin](https://github.com/cadasto/openehr-assistant-dev-plugin)
> (Claude Code + Cursor). The user-facing
> [openehr-assistant plugin](https://github.com/cadasto/openehr-assistant-plugin)
> wraps this server for clinical end users — do not confuse the two audiences.

## Project overview

- The openEHR Assistant MCP Server is a PHP 8.4 MCP server that exposes openEHR tools, prompts, and resources for MCP clients. The codebase is PSR-compliant and structured around attribute-driven discovery. See `README.md` for the feature overview and capabilities.
- The project uses the `modelcontextprotocol/php-sdk` to register tools (`#[McpTool]`), prompts (`#[McpPrompt]`), resources (`#[McpResourceTemplate]`/`#[McpResource]`), and completion providers (`#[CompletionProvider]`).

## Repository layout (high level)

- `public/index.php`: entrypoint; registers MCP capabilities (tools, prompts, resources) and starts the server. It uses a file-based cache for MCP discovery to improve startup performance.
- `src/Tools`: MCP tools, each method annotated with `#[McpTool(...)]`.
- `src/Prompts`: MCP prompts, each class annotated with `#[McpPrompt(...)]`.
- `src/Resources`: MCP resources and resource templates (`#[McpResourceTemplate]`, `#[McpResource]`).
- `src/CompletionProviders`: completion providers implementing `Mcp\Capability\Completion\ProviderInterface`.
- `src/Apis`: internal API clients for CKM and other services.
- `resources/`: static assets such as guides, BMM JSON, and terminology files.
- `tests/`: PHPUnit tests and configuration for tools, prompts, resources, and completion providers.

## Configuration & environment

- **Runtime:** Docker-only (PHP 8.4 via `.docker/Dockerfile`); services `app`, `ingress` (Caddy), and dev-only `node` (conformance / npx). The full env-var table, service detail, and the discovery-cache gotcha live in [`docs/development.md`](docs/development.md).
- **Transports:** `streamable-http` (default; dev port `:8343`) and `stdio` (`php public/index.php --transport=stdio`).
- **DNS-rebinding (gotcha):** the `streamable-http` transport (SDK ≥ 0.6) accepts only hosts in `MCP_ALLOWED_HOSTS` (loopback by default); set it to the reverse-proxy host / public domain when deployed behind a proxy.
- **Versioning:** `APP_VERSION` in `src/constants.php`.

## Conventions

Coding standard (PSR-12), namespaces, and MCP capability authoring (tools, prompts, resources, completion providers — including the prompt policy split and the discovery-cache gotcha) are canonical in [`docs/conventions.md`](docs/conventions.md). Process rules when working here:

- **Commits:** [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) with a scope, e.g. `feat(tools):`, `fix(resources):`, `docs:`.
- **CHANGELOG.md:** keep `## [Unreleased]` entries **short and high-level** — one-line bullets naming the artefact class and scope; do not enumerate individual files, classes, or audit details (those belong in commit messages / PR bodies).
- **Branching:** feature branches + pull requests; PR validation runs on every push.
- **Before pushing:** run `make ci` (PHPStan + tests).

## Discovering and running developer tools

Tool definitions are declared in `composer.json` under `scripts`. The runtime is
**Docker-only** — there is no host PHP/Composer; all `php`, `composer`, and
`vendor/bin/*` commands run inside the `app` dev container, or they fail
([ADR-0004](docs/decisions/0004-docker-only-runtime.md)).

```bash
make up-dev      # start dev containers
make install     # install Composer dev dependencies
make conformance # MCP conformance suite (stack must be up)
```

- Full `docker compose … exec` invocations for **tests**, **PHPStan**, and
  **coverage**, plus configuration and the discovery-cache gotcha →
  [`docs/development.md`](docs/development.md) and [`docs/testing.md`](docs/testing.md).
- Run a single test class: `vendor/bin/phpunit --filter SomeTest` (in the container).

### Looking up openEHR specification content

When authoring or editing guides, prompts, BMM JSON, terminology, AQL grammar, or anything that must track the upstream openEHR standards, **do not guess or rely on training memory** — retrieve from `specifications.openehr.org`, cheapest representation first: `llms.txt` index → `.md` twin → structured `/api/*.json` → HTML. The Markdown twin omits per-class attribute/function/invariant tables; use `type_specification_get` (BMM-backed) or the HTML page for those. Track the `development` branch, not `latest`.

The full policy, fall-through order, and failure modes live in the [`spec-lookup` how-to](resources/guides/howto/spec-lookup.md) (read via `guide_get(category="howto", name="spec-lookup")`, also stated in `resources/server-instructions.md`) and [ADR-0005](docs/decisions/0005-spec-aligned-content-retrieval.md).

### Guides and specification alignment

- When adding or editing guides (e.g. under `resources/guides/`) or prompts that describe a standard (e.g. AQL), keep wording aligned with the authoritative spec and any formal grammar in the repo.
- Avoid duplicate or misplaced paragraphs in guide files.
- **Archetypes/templates**: Guides under `resources/guides/archetypes/` and `resources/guides/templates/` should stay consistent with openEHR modelling docs and ADL/OET conventions referenced in the project.
- **Simplified Formats**: Guides under `resources/guides/simplified_formats/` (Flat and Structured JSON serialization; Web Template field identifiers, ctx, pipe suffixes, underscore prefix) must align with the authoritative openEHR **ITS Simplified Formats** specification — retrieve it per the spec-lookup policy below, not from memory.
- **Authoring conventions and templates** — guide markdown style, spec-digest authoring rules, and the copy-ready digest skeleton all live under [`src/templates/`](src/templates/). Start there before adding or modifying a guide.

### Clinical modelling and governance

- When adding or changing guidance on archetypes, templates, or clinical modelling, uphold: **two-level modelling** (RM vs archetype/template); **single-concept archetypes**; **no workflow/UI in archetypes** (those belong in templates or apps); **reusability and semantic correctness** over app-specific convenience; **CKM and spec alignment**.

## Learned User Preferences

- When updating guides under `resources/guides/`, prefer substantive improvements that add value; avoid trivial or small changes that do not improve the guidelines.
- Guide content is consumed by AI agents: keep it short, concise, and scannable.

## Learned Workspace Facts

- Repo tooling that can be implemented in PHP should live as classes in `src/` with CLI entrypoints (e.g. `scripts/*.php`) and `composer.json` script entries; update AGENTS.md and related docs when changing such tooling.

